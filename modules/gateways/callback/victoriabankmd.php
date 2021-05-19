<?php
/**
 * WHMCS Merchant Gateway 3D Secure Callback File
 *
 * The purpose of this file is to demonstrate how to handle the return post
 * from a 3D Secure Authentication process.
 *
 * It demonstrates verifying that the payment gateway module is active,
 * validating an Invoice ID, checking for the existence of a Transaction ID,
 * Logging the Transaction for debugging and Adding Payment to an Invoice.
 *
 * Users are expected to be redirected to this file as part of the 3D checkout
 * flow so it also demonstrates redirection to the invoice upon completion.
 *
 * For more information, please refer to the online documentation.
 *
 * @see https://developers.whmcs.com/payment-gateways/callbacks/
 *
 * @copyright Copyright (c) WHMCS Limited 2017
 * @license http://www.whmcs.com/license/ WHMCS Eula
 */

// Require libraries needed for gateway module functions.


require_once __DIR__ . '/../../../init.php';
$whmcs->load_function('gateway');
$whmcs->load_function('invoice');

use WHMCS\Database\Capsule;

// Detect module name from filename.
$gatewayModuleName = basename(__FILE__, '.php');

// Fetch gateway configuration parameters.
$gatewayParams = getGatewayVariables($gatewayModuleName);

// Die if module is not active.
if (!$gatewayParams['type']) {
	die("Module Not Activated");
}

// Retrieve data returned in payment gateway callback
$success = $_POST["ACTION"];
$invoiceId = $_POST["ORDER"];
$terminalId = $_POST["TERMINAL"];
$paymentAmount = $_POST["AMOUNT"];
$hash = $_POST["P_SIGN"];
$transactionId = $_POST['ORDER'].'-'.$_POST['RRN'].'-'.$_POST['INT_REF'];

$transactionStatus = ($success==0 ? 'Success' : 'Failure');

/**
 * Validate Callback Invoice ID.
 *
 * Checks invoice ID is a valid invoice number. Note it will count an
 * invoice in any status as valid.
 *
 * Performs a die upon encountering an invalid Invoice ID.
 *
 * Returns a normalised invoice ID.
 *
 * @param int $invoiceId Invoice ID
 * @param string $gatewayName Gateway Name
 */

$transaction = Capsule::table('mod_victoriabank_transactions')->select('*')->where('orderid','=', $_POST["ORDER"])->limit(1)->get();
if(count($transaction)) {
	if($transaction[0]->orderid !== $transaction[0]->invoiceid) {
		$invoiceId = $transaction[0]->invoiceid;
	}
}
$invoiceId = checkCbInvoiceID($invoiceId, $gatewayParams['name']);

/**
 * Check Callback Transaction ID.
 *
 * Performs a check for any existing transactions with the same given
 * transaction number.
 *
 * Performs a die upon encountering a duplicate.
 *
 * @param string $transactionId Unique Transaction ID
 */
checkCbTransID($transactionId);

$paymentSuccess = false;

if ($success == 0) {
	$invoice_data = localAPI('GetInvoice', array(
		'invoiceid' => $invoiceId
	), $gatewayParams['localapi_user']);
	
	$client_data = localAPI('GetClientsDetails', array(
		'clientid' => (int) $invoice_data['userid'],
	), $gatewayParams['localapi_user']);
	
	$payed = $invoice_data['total'];
	$fees  = $payed * 0.03;
	
	if($_POST['TRTYPE'] == 0) {
		/**
		* Add Invoice Payment.
		*
		* Applies a payment transaction entry to the given invoice ID.
		*
		* @param int $invoiceId		 Invoice ID
		* @param string $transactionId  Transaction ID
		* @param float $paymentAmount   Amount paid (defaults to full balance)
		* @param float $paymentFee	  Payment fee (optional)
		* @param string $gatewayModule  Gateway module name
		*/
		localAPI('addInvoicePayment', array(
			'invoiceid' => (int) $invoiceId,
			'transid' => $transactionId,
			'payed' => $payed,
			'fees' => $fees,
			'gateway' => $gatewayParams['paymentmethod']
		), $gatewayParams['localapi_user']);
		
		$_POST['clientid'] = $client_data['userid'];
		$_POST['invoiceid'] = $invoiceId;
		
		/**
		* Log Transaction.
		*
		* Add an entry to the Gateway Log for debugging purposes.
		*
		* The debug data can be a string or an array. In the case of an
		* array it will be
		*
		* @param string $gatewayName		Display label
		* @param string|array $debugData	Data to log
		* @param string $transactionStatus  Status
		*/
		logTransaction($gatewayParams['name'], $_POST, $transactionStatus);
		
		// send email check
		$email_variables = array(
			'transaction_rrn' => $_POST['RRN'],
			'transaction_approval' => $_POST['APPROVAL'],
			'transaction_time' => date('d.m.Y H:i:s'),
			'transaction_mn' => $gatewayParams['merchant_name'],
			'transaction_country' => 'Moldova',
			'transaction_website' => 'https://innovahosting.net',
			'transaction_invoiceid' => $_POST['ORDER'],
			'transaction_cn' => $client_data['fullname'],
			'transaction_amount' => $_POST['AMOUNT'],
			'transaction_currency' => $_POST['CURRENCY'],
			'transaction_cc' => substr($_POST['CARD'], 12, 4),
		);	
		
		$result = localAPI('SendEmail', array(
			'messagename' => $gatewayParams['email_title'],
			'id' => $invoiceId,
			'customvars' => base64_encode(serialize($email_variables)),
// 			'customtype' => 'invoice'
		), $gatewayParams['localapi_user']);
		if($result['result'] !== 'success') {
			logTransaction($gatewayParams['name'], array('action'=>'email_check_sending', 'result' => $result), $transactionStatus);
		}
		
		// notify bank about finishing transaction
		$bank_response = completion_response($gatewayParams, $client_data, $_POST);
		logTransaction($gatewayParams['name'], array('action'=>'completion_response', 'request' => $bank_response['request'], 'result' => $bank_response['response']), $transactionStatus);
	}
	
	$paymentSuccess = true;
} else {
	logTransaction($gatewayParams['name'], $_POST, $transactionStatus);
}

// log creditcard transaction
if($_POST['TRTYPE'] == 0) {
	Capsule::table('mod_victoriabank_transactions')->where('orderid','=',$_POST['ORDER'])->update(
		[
			'bin' => $_POST['BIN'],
			'card' => $_POST['CARD'],
			'rrn' => $_POST['RRN'],
			'text' => $_POST['TEXT'],
			'invoiceid' => $invoiceId,
			'pending' => 0,
			'timestamp' => Capsule::raw('CURRENT_TIMESTAMP()')
		]
	);
}

function completion_response($gateway, $client_data, $data) {
	$offset = intval(date('O')/100);
	$timestamp = date('YmdHis', date('U')-$offset*3600);
	
	$array = array(
		'ORDER'		=> $data['ORDER'],
		'AMOUNT'	=> $data['AMOUNT'],
		'CURRENCY'	=> $data['CURRENCY'],
		'RRN'		=> $data['RRN'],
		'INT_REF'	=> $data['INT_REF'],
		'TRTYPE'	=> 21,
		'TERMINAL'	=> $gateway['terminal_id'],
		'TIMESTAMP'	=> $timestamp,
		'MERCH_GMT' => $offset,
		'NONCE'		=> generate_nonce(),
		'P_SIGN'	=> '',
		
		'DESC' => 'Servicii hosting',
		'MERCH_URL' => $gateway['systemurl'],
		'EMAIL' => $client_data['email'],
		'COUNTRY' => strtolower($client_data['countrycode']),
		'BACKREF' => $gateway['systemurl'],
		'MERCH_NAME' => $gateway['merchant_name'],
		'MERCHANT' => $gateway['card_acceptor_id'],
		'TERMINAL' => $gateway['terminal_id'],
		'LANG' => 'en',
		'MERCH_ADDRESS' => $gateway['physical_address'],
	);
	
	list($array['P_SIGN'], $MAC) = P_SIGN_ENCRYPT($array['ORDER'], $array['TIMESTAMP'], $array['TRTYPE'], $array['AMOUNT'], $array['NONCE']);
	
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $gateway['post_url']);
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($array));
	curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));
	$result = curl_exec($ch);
	curl_close($ch);
	
	$array['MAC'] = $MAC;
	return array('request' => $array, 'response' => $result);
}

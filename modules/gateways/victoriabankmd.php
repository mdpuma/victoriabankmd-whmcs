<?php
/**
 * WHMCS Sample Merchant Gateway Module
 *
 * This sample file demonstrates how a merchant gateway module supporting
 * 3D Secure Authentication, Captures and Refunds can be structured.
 *
 * If your merchant gateway does not support 3D Secure Authentication, you can
 * simply omit that function and the callback file from your own module.
 *
 * Within the module itself, all functions must be prefixed with the module
 * filename, followed by an underscore, and then the function name. For this
 * example file, the filename is "merchantgateway" and therefore all functions
 * begin "victoriabankmd_".
 *
 * For more information, please refer to the online documentation.
 *
 * @see https://developers.whmcs.com/payment-gateways/
 *
 * @copyright Copyright (c) WHMCS Limited 2017
 * @license http://www.whmcs.com/license/ WHMCS Eula
 */

if (!defined("WHMCS")) {
	die("This file cannot be accessed directly");
}

use WHMCS\Database\Capsule;

/**
 * Define module related meta data.
 *
 * Values returned here are used to determine module related capabilities and
 * settings.
 *
 * @see https://developers.whmcs.com/payment-gateways/meta-data-params/
 *
 * @return array
 */
function victoriabankmd_MetaData()
{
	return array(
		'DisplayName' => 'Victoria Ecommerce',
		'APIVersion' => '1.1', // Use API Version 1.1
		'DisableLocalCreditCardInput' => false,
		'TokenisedStorage' => false,
	);
}

/**
 * Define gateway configuration options.
 *
 * The fields you define here determine the configuration options that are
 * presented to administrator users when activating and configuring your
 * payment gateway module for use.
 *
 * Supported field types include:
 * * text
 * * password
 * * yesno
 * * dropdown
 * * radio
 * * textarea
 *
 * Examples of each field type and their possible configuration parameters are
 * provided in the sample function below.
 *
 * @see https://developers.whmcs.com/payment-gateways/configuration/
 *
 * @return array
 */
function victoriabankmd_config()
{
	return array(
		'FriendlyName' => array(
			'Type' => 'System',
			'Value' => 'Victoria bank eCommerce',
		),
		'terminal_id' => array(
			'FriendlyName' => 'Terminal ID',
			'Type' => 'text',
			'Size' => '25',
			'Default' => '',
			'Description' => '',
		),
		'card_acceptor_id' => array(
			'FriendlyName' => 'Card Acceptor ID',
			'Type' => 'text',
			'Size' => '25',
			'Default' => '',
			'Description' => '',
		),
		'post_url' => array(
			'FriendlyName' => 'eCommerce URL',
			'Type' => 'text',
			'Size' => '30',
			'Default' => 'https://vb059.vb.md/cgi-bin/cgi_link',
			'Description' => 'Default URL: https://egateway.victoriabank.md/cgi-bin/cgi_link; 3D secure: https://vb059.vb.md/cgi-bin/cgi_link',
		),
		'physical_address' => array(
			'FriendlyName' => 'Physical office address',
			'Type' => 'text',
			'Size' => '64',
			'Default' => '',
			'Description' => '',
		),
		'merchant_name' => array(
			'FriendlyName' => 'Merchant Name',
			'Type' => 'text',
			'Size' => '64',
			'Default' => '',
			'Description' => 'This is informative merchant name on gateway',
		),
		"localapi_user" => array(
			"FriendlyName" => "Username for LocalAPI",
			"Type" => "text",
			"Size" => "50",
			"Description" => "Read more here https://developers.whmcs.com/api/internal-api/"
		),
		"email_title" => array(
			"FriendlyName" => "Name of the client email template for electronic check",
			"Type" => "text",
			"Size" => "50",
			"Description" => "Name of email template for electronic check <a href='admin/configemailtemplates.php'>Email Templates</a>"
		)
	);
}

function victoriabankmd_link($params)
{
	// Gateway Configuration Parameters
	$accountId = $params['accountID'];
	$secretKey = $params['secretKey'];
	$testMode = $params['testMode'];
	$dropdownField = $params['dropdownField'];
	$radioField = $params['radioField'];
	$textareaField = $params['textareaField'];

	// Invoice Parameters
	$invoiceId = $params['invoiceid'];
	$description = $params["description"];
	$amount = $params['amount'];
	$currencyCode = $params['currency'];
	
	// Client Parameters
	$firstname = $params['clientdetails']['firstname'];
	$lastname = $params['clientdetails']['lastname'];
	$email = $params['clientdetails']['email'];
	$address1 = $params['clientdetails']['address1'];
	$address2 = $params['clTerminalientdetails']['address2'];
	$city = $params['clientdetails']['city'];
	$state = $params['clientdetails']['state'];
	$postcode = $params['clientdetails']['postcode'];
	$country = $params['clientdetails']['country'];
	$phone = $params['clientdetails']['phonenumber'];

	// System Parameters
	$companyName = $params['companyname'];
	$systemUrl = $params['systemurl'];
	$returnUrl = $params['returnurl'];
	$langPayNow = $params['langpaynow'];
	$moduleDisplayName = $params['name'];
	$moduleName = $params['paymentmethod'];
	$whmcsVersion = $params['whmcsVersion'];
	$url = $params['post_url'];
	
	$offset = intval(date('O')/100);
	$timestamp = date('YmdHis', date('U')-$offset*3600);
	
	//check if there is transaction for same order with RRN
	$i=0;
	$new_order_id = false;
	$first_attempt = false;
	do {
		$rrn_exists = false;
		$rrn_array = Capsule::table('mod_victoriabank_transactions')->select('rrn','text','pending')->where('orderid','=',$invoiceId)->get();
		if(count($rrn_array) == 1) {
			$last_rrn = intval($rrn_array[0]->rrn);
			$last_text = $rrn_array[0]->text;
			$is_pending = intval($rrn_array[0]->pending);
			if($is_pending == 1) {
				$rrn_exists = false;
				$new_order_id = false;
				break;
			}
			$rrn_exists=true;
			$new_order_id=true;
			if($i==0) {
				$invoiceId = $invoiceId*10;
			} else {
				$invoiceId++;
			}
		} else {
			$new_order_id=true;
			if($i==0) $first_attempt = true;
			break;
		}
		$i++;
	} while($rrn_exists == true && $i < 10);
	
	if($new_order_id == true || $first_attempt == true) {
		Capsule::table('mod_victoriabank_transactions')->insert(
		[
			'pending' => 1,
			'orderid' => $invoiceId,
			'invoiceid' => $params['invoiceid']
		]);
	}
	
	$postfields = array(
		'AMOUNT' => $amount,
		'CURRENCY' => strtoupper($currencyCode),
		'ORDER' => $invoiceId,
		'DESC' => $description,
		'MERCH_NAME' => $params['merchant_name'],
		'MERCH_URL' => $systemUrl,
		'MERCHANT' => $params['card_acceptor_id'],
		'TERMINAL' => $params['terminal_id'],
		'EMAIL' => $email,
		'TRTYPE' => '0',
		'COUNTRY' => strtolower($country),
		'NONCE' => generate_nonce(),
		'BACKREF' => $systemUrl.'/paymentok.php',
		'MERCH_GMT' => $offset,
		'TIMESTAMP' => $timestamp,
		'P_SIGN' => '',
		'LANG' => 'en',
		'MERCH_ADDRESS' => $params['physical_address'],
	);
	list($postfields['P_SIGN'], $MAC) = P_SIGN_ENCRYPT($postfields['ORDER'], $postfields['TIMESTAMP'], $postfields['TRTYPE'], $postfields['AMOUNT'], $postfields['NONCE']);

	$htmlOutput = '<form method="post" action="' . $url . '">';
	foreach ($postfields as $k => $v) {
		$htmlOutput .= '<input type="hidden" name="' . $k . '" value="' . $v . '" />';
	}
	$htmlOutput .= '<!--<input type="hidden" name="MAC" value="' . $MAC . '" />-->';
	$htmlOutput .= '<input type="submit" value="' . $langPayNow . '" />';
	$htmlOutput .= '</form>';

	return $htmlOutput;
}

/**
 * Refund transaction.
 *
 * Called when a refund is requested for a previously successful transaction.
 *
 * @param array $params Payment Gateway Module Parameters
 *
 * @see https://developers.whmcs.com/payment-gateways/refunds/
 *
 * @return array Transaction response status
 */
function victoriabankmd_refund($params)
{
// 	Transaction Parameters
// 	$transactionIdToRefund = $params['transid'];
// 	$refundAmount = $params['amount'];
// 	$currencyCode = $params['currency'];

//	Client Parameters
// 	$firstname = $params['clientdetails']['firstname'];
// 	$lastname = $params['clientdetails']['lastname'];
// 	$email = $params['clientdetails']['email'];
// 	$address1 = $params['clientdetails']['address1'];
// 	$address2 = $params['clientdetails']['address2'];
// 	$city = $params['clientdetails']['city'];
// 	$state = $params['clientdetails']['state'];
// 	$postcode = $params['clientdetails']['postcode'];
// 	$country = $params['clientdetails']['country'];
// 	$phone = $params['clientdetails']['phonenumber'];

//	 System Parameters
// 	$companyName = $params['companyname'];
// 	$systemUrl = $params['systemurl'];
// 	$langPayNow = $params['langpaynow'];
// 	$moduleDisplayName = $params['name'];
// 	$moduleName = $params['paymentmethod'];
// 	$whmcsVersion = $params['whmcsVersion'];

//	 perform API call to initiate refund and interpret result
	list($data['ORDER'], $data['RRN'], $data['INT_REF']) = explode('-', $params['transid']);
	
// 	$invoice_data = localAPI('GetInvoice', array(
// 		'invoiceid' => $params['invoiceid']
// 	), $params['localapi_user']);
	
	$transaction_data = localAPI('GetTransactions', array(
		'transid' => $params['transid']
	), $params['localapi_user']);
	
	if($transaction_data['totalresults'] !== 1) {
		logTransaction($params['name'], array('action'=>'victoriabankmd_refund', 'request' => array(), 'result' => array('error'=>'Cant find transaction by id'), 'Failed'));
		return;
	}
	
	$amount = $transaction_data['transactions']['transaction'][0]['amountin'];
	
// 	var_dump($params);
// 	var_dump($transaction_data);
// 	var_dump($invoice_data);
	
	$offset = intval(date('O')/100);
	$timestamp = date('YmdHis', date('U')-$offset*3600);
	
	$array = array(
		'ORDER'		=> $data['ORDER'],
		'AMOUNT'	=> $amount,
		'CURRENCY'	=> $params['currency'],
		'RRN'		=> $data['RRN'],
		'INT_REF'	=> $data['INT_REF'],
		'TRTYPE'	=> 24,
		'TERMINAL'	=> $params['terminal_id'],
		'TIMESTAMP'	=> $timestamp,
		'MERCH_GMT' => $offset,
		'NONCE'		=> generate_nonce(),
		'P_SIGN'	=> '',
		
		'DESC' => 'Servicii hosting',
		'MERCH_URL' => $params['systemurl'],
		'EMAIL' => $params['clientdetails']['email'],
		'COUNTRY' => strtolower($params['clientdetails']['countrycode']),
		'BACKREF' => $params['systemurl'],
		'MERCH_NAME' => $params['merchant_name'],
		'MERCHANT' => $params['card_acceptor_id'],
		'TERMINAL' => $params['terminal_id'],
		'LANG' => 'en',
		'MERCH_ADDRESS' => $params['physical_address'],
	);
	
	list($array['P_SIGN'], $MAC) = P_SIGN_ENCRYPT($array['ORDER'], $array['TIMESTAMP'], $array['TRTYPE'], $array['AMOUNT'], $array['NONCE']);
	
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $params['post_url']);
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($array));
	curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));
	$result = curl_exec($ch);
	curl_close($ch);
	
	$array['MAC'] = $MAC;
	
	// Referenced Transaction was successfully reversed
	
	logTransaction($params['name'], array('action'=>'victoriabankmd_refund', 'request' => $array, 'result' => $result), $transactionStatus);
	
	return array(
// 		'success' if successful, otherwise 'declined', 'error' for failure
		'status' => 'success',
// 		Data to be recorded in the gateway log - can be a string or array
		'rawdata' => $result,
// 		Unique Transaction ID for the refund transaction
		'transid' => $params['transid'],
// 		Optional fee amount for the fee value refunded
		'fees' => $params['amount']*0.03,
	);
}


function generate_nonce() {
	$result='';
	for($i=0; $i<20; $i++) {
		$result.=rand(0,1);
	}
	return $result;
}

function P_SIGN_ENCRYPT($OrderId, $Timestamp, $trtType, $Amount, $nonce)
{
	$MAC  = '';
	$RSA_KeyPath = __DIR__.'/secure/my_private.key';
	if(!is_file($RSA_KeyPath)) return 'Inexistent private key file';
	$RSA_Key = file_get_contents ($RSA_KeyPath);
	$Data = array (
			'ORDER' => $OrderId,
			'NONCE' => $nonce,
			'TIMESTAMP' => $Timestamp,
			'TRTYPE' => $trtType,
			'AMOUNT' => $Amount
		);
		
	if (!$RSA_KeyResource = openssl_get_privatekey ($RSA_Key)) return 'Failed get private key';
	$RSA_KeyDetails = openssl_pkey_get_details ($RSA_KeyResource);
	$RSA_KeyLength = $RSA_KeyDetails['bits']/8;
	
	foreach ($Data as $Id => $Filed) $MAC .= strlen ($Filed).$Filed;
	
	$First = '0001';
	$Prefix = '003020300C06082A864886F70D020505000410';
	$MD5_Hash = md5 ($MAC); 
	$Data = $First;
	
	$paddingLength = $RSA_KeyLength - strlen ($MD5_Hash)/2 - strlen ($Prefix)/2 - strlen ($First)/2;
	for ($i = 0; $i < $paddingLength; $i++) $Data .= "FF";
	
	$Data .= $Prefix.$MD5_Hash;
	$BIN = pack ("H*", $Data);
	
	if (!openssl_private_encrypt ($BIN, $EncryptedBIN, $RSA_Key, OPENSSL_NO_PADDING)) 
	{
		while ($msg = openssl_error_string()) echo $msg . "<br />\n";
		die ('Failed encrypt');
	}
	
	$P_SIGN = bin2hex ($EncryptedBIN);
	
	return array(strtoupper ($P_SIGN), $MAC);
}

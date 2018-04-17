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

// Detect module name from filename.
$gatewayModuleName = basename(__FILE__, '.php');

// Fetch gateway configuration parameters.
$gatewayParams = getGatewayVariables($gatewayModuleName);

// Die if module is not active.
if (!$gatewayParams['type']) {
    die("Module Not Activated");
}

// Retrieve data returned in payment gateway callback
// Varies per payment gateway
$success = $_POST["ACTION"];
$invoiceId = $_POST["ORDER"];
$terminalId = $_POST["49802477"];
$paymentAmount = $_POST["AMOUNT"];
$hash = $_POST["P_SIGN"];
$transactionId = $_POST['TERMINAL'].'-'.$_POST['ORDER'];

//     [Function] => TransResponse
//     [TERMINAL] => 49802477
//     [TRTYPE] => 0
//     [ORDER] => 123690
//     [AMOUNT] => 13.68
//     [CURRENCY] => MDL
//     [ACTION] => 3
//     [RC] => -17
//     [TEXT] => Access denied
//     [APPROVAL] => 
//     [RRN] => 
//     [INT_REF] => 
//     [TIMESTAMP] => 20180329141818
//     [NONCE] => 11101000100110
//     [P_SIGN] => 5C84974FEA60FAEFE95CA1F2C41AE636918E772BB045CD9BEA26DE0C8BB70E2AE162FF16A9BFD9FE073259B2C5A9CBB03B9E8B60D163BF59EAC2A4766291B2213926C1DAD8359D0544ECF6CAC938BEB69429A02A9FBC92A2D3FB8683646203168EBC3A16F292FB6CDBAB72CA44751DCBDBA162FD6C1BA3B66869596BFEC2E3AB143742EE7D8591DBD0BAB23BE0D7FBBC3AD4A899CD253EE0A1F26262DC67C8C72FCCB1DB9557B7E9BAF80BCAB9B5B24D788C6C7DCA445EEB17E969A3EE617001ACE8B2627FB04A2356EC97B579BE624F800B15E6235CB2AAFB65633F67E063D51B6EACA3B155F8D1C0CAF3B5D3BBC3F26917726B489C704DC35A1CD119962409
//     [BIN] => 
//     [CARD] => 4779XXXXXXXX1488
//     [AUTH] => 
//     [ECI] => 

$postdata = file_get_contents("php://input");
error_log("postarray=".print_r($_POST, true), 3, "error_log");

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
$invoiceId = checkCbInvoiceID($invoiceId/10, $gatewayParams['name']);

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

/**
 * Log Transaction.
 *
 * Add an entry to the Gateway Log for debugging purposes.
 *
 * The debug data can be a string or an array. In the case of an
 * array it will be
 *
 * @param string $gatewayName        Display label
 * @param string|array $debugData    Data to log
 * @param string $transactionStatus  Status
 */
logTransaction($gatewayParams['name'], $_POST, $transactionStatus);

$paymentSuccess = false;

if ($success == 0) {

    /**
     * Add Invoice Payment.
     *
     * Applies a payment transaction entry to the given invoice ID.
     *
     * @param int $invoiceId         Invoice ID
     * @param string $transactionId  Transaction ID
     * @param float $paymentAmount   Amount paid (defaults to full balance)
     * @param float $paymentFee      Payment fee (optional)
     * @param string $gatewayModule  Gateway module name
     */
     
     
    $invoice_data = localAPI('GetInvoice', array(
        'invoiceid' => $invoiceId
    ), $gatewayParams['localapi_user']);
    
    $payed = $invoice_data['total'];
    $fees  = $payed * 0.03;
    
    // здесь осуществляем обработку данного платежа
    localAPI('addInvoicePayment', array(
        'invoiceid' => (int) $invoiceId,
        'transid' => $transactionId,
        'payed' => $payed,
        'fees' => $fees,
        'gateway' => $gatewayParams['paymentmethod']
    ), $gatewayParams['localapi_user']);

    $result = localAPI('SendEmail', array(
        'messagename' => $gatewayParams['email_title'],
        'id' => $invoiceId,
        'customvars' => base64_encode(serialize(array(
            'transaction_rrn' => $_POST['RRN'],
            'transaction_auth' => $_POST['AUTH'],
            'transaction_time' => date('d.m.Y H:i:s')
        )))
    ), $gatewayParams['localapi_user']);
    logTransaction($gatewayParams['name'], array('status'=>'Email is sent', 'result' => $result), $transactionStatus);
    
    $paymentSuccess = true;

}
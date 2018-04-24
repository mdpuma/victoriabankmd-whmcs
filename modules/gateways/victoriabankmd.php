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
        // the friendly display name for a payment gateway should be
        // defined here for backwards compatibility
        'FriendlyName' => array(
            'Type' => 'System',
            'Value' => 'Victoria bank eCommerce',
        ),
        // a text field type allows for single line text input
        'terminal_id' => array(
            'FriendlyName' => 'Terminal ID',
            'Type' => 'text',
            'Size' => '25',
            'Default' => '',
            'Description' => '',
        ),
        // a password field type allows for masked text input
        'card_acceptor_id' => array(
            'FriendlyName' => 'Card Acceptor ID',
            'Type' => 'text',
            'Size' => '25',
            'Default' => '',
            'Description' => '',
        ),
        // a password field type allows for masked text input
        'post_url' => array(
            'FriendlyName' => 'eCommerce URL',
            'Type' => 'text',
            'Size' => '30',
            'Default' => '',
            'Description' => '',
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

    // Return HTML form for redirecting user to 3D Auth.

    $url = $params['post_url'];

//     <form action="https://egateway.victoriabank.md/cgi-bin/cgi_link?" method="post">
// AMOUNT	<input value="1"name="AMOUNT" /><br />
// CURRENCY	<input value="MDL" name="CURRENCY" /><br />
// ORDER	<input value="" name="ORDER" /><br />
// DESC	<input value="test" name="DESC" /><br />
// MERCH_NAME	<input value="Magazin SRL" name="MERCH_NAME" /><br />
// MERCH_URL	<input value="www.test.md" name="MERCH_URL" /><br />
// MERCHANT	<input value="498000049801234" name="MERCHANT" /><br />
// TERMINAL	<input value="49801234" name="TERMINAL" /><br />
// EMAIL	<input value="test@test.md" name="EMAIL" /><br />
// TRTYPE	<input value="0" name="TRTYPE" /><br />
// COUNTRY	<input value="md" name="COUNTRY" /><br />
// NONCE	<input value="11111111000000011111" name="NONCE" /><br />
// BACKREF	<input value="http://www.test.md/" name="BACKREF" /><br />
// MERCH_GMT <input value="2" name="MERCH_GMT" /><br />
// TIMESTAMP <input value="20110627060100" name="TIMESTAMP" /><br />
// P_SIGN <input value="" name="P_SIGN" /><br />
// LANG <input value="en" name="LANG" /><br />
// MERCH_ADDRESS <input value="" name="MERCH_ADDRESS" /><br />
// <input type="submit" value="Submit" />
// </form>
    
    $offset = intval(date('O')/100);
    $timestamp = date('YmdHis', date('U')-$offset*3600);
    
    // 'TIMESTAMP' => date('YmdHis'), //  YYYYMMDDHHMMSS
    $postfields = array(
        'AMOUNT' => (int) $amount,
        'CURRENCY' => strtoupper($currencyCode),
        'ORDER' => $invoiceId,
        'DESC' => $description,
        'MERCH_NAME' => $params['merchant_name'], /// ?????
        'MERCH_URL' => $systemUrl, /// ?????
        'MERCHANT' => $params['card_acceptor_id'],
        'TERMINAL' => $params['terminal_id'],
        'EMAIL' => $email,
        'TRTYPE' => '0',
        'COUNTRY' => strtolower($country), /// ?????
        'NONCE' => generate_nonce(), /// ?????
        'BACKREF' => $systemUrl.'/paymentok.php',
        'MERCH_GMT' => intval(date('O')/100),
        'TIMESTAMP' => $timestamp,
        'P_SIGN' => '', /// ?????
        'LANG' => 'en', /// ?????
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
	$RSA_KeyPath = 'modules/gateways/secure/my_private.key';
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


/**
 * Capture payment.
 *
 * Called when a payment is to be processed and captured.
 *
 * The card cvv number will only be present for the initial card holder present
 * transactions. Automated recurring capture attempts will not provide it.
 *
 * @param array $params Payment Gateway Module Parameters
 *
 * @see https://developers.whmcs.com/payment-gateways/merchant-gateway/
 *
 * @return array Transaction response status
 */
// function victoriabankmd_capture($params)
// {
//     // Gateway Configuration Parameters
//     $accountId = $params['accountID'];
//     $secretKey = $params['secretKey'];
//     $testMode = $params['testMode'];
//     $dropdownField = $params['dropdownField'];
//     $radioField = $params['radioField'];
//     $textareaField = $params['textareaField'];
// 
//     // Invoice Parameters
//     $invoiceId = $params['invoiceid'];
//     $description = $params["description"];
//     $amount = $params['amount'];
//     $currencyCode = $params['currency'];
// 
//     // Credit Card Parameters
//     $cardType = $params['cardtype'];
//     $cardNumber = $params['cardnum'];
//     $cardExpiry = $params['cardexp'];
//     $cardStart = $params['cardstart'];
//     $cardIssueNumber = $params['cardissuenum'];
//     $cardCvv = $params['cccvv'];
// 
//     // Client Parameters
//     $firstname = $params['clientdetails']['firstname'];
//     $lastname = $params['clientdetails']['lastname'];
//     $email = $params['clientdetails']['email'];
//     $address1 = $params['clientdetails']['address1'];
//     $address2 = $params['clientdetails']['address2'];
//     $city = $params['clientdetails']['city'];
//     $state = $params['clientdetails']['state'];
//     $postcode = $params['clientdetails']['postcode'];
//     $country = $params['clientdetails']['country'];
//     $phone = $params['clientdetails']['phonenumber'];
// 
//     // System Parameters
//     $companyName = $params['companyname'];
//     $systemUrl = $params['systemurl'];
//     $returnUrl = $params['returnurl'];
//     $langPayNow = $params['langpaynow'];
//     $moduleDisplayName = $params['name'];
//     $moduleName = $params['paymentmethod'];
//     $whmcsVersion = $params['whmcsVersion'];
// 
//     // perform API call to capture payment and interpret result
// 
//     return array(
//         // 'success' if successful, otherwise 'declined', 'error' for failure
//         'status' => 'success',
//         // Data to be recorded in the gateway log - can be a string or array
//         'rawdata' => $responseData,
//         // Unique Transaction ID for the capture transaction
//         'transid' => $transactionId,
//         // Optional fee amount for the fee value refunded
//         'fees' => $feeAmount,
//     );
// }


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
// function victoriabankmd_refund($params)
// {
//     // Gateway Configuration Parameters
//     $accountId = $params['accountID'];
//     $secretKey = $params['secretKey'];
//     $testMode = $params['testMode'];
//     $dropdownField = $params['dropdownField'];
//     $radioField = $params['radioField'];
//     $textareaField = $params['textareaField'];
// 
//     // Transaction Parameters
//     $transactionIdToRefund = $params['transid'];
//     $refundAmount = $params['amount'];
//     $currencyCode = $params['currency'];
// 
//     // Client Parameters
//     $firstname = $params['clientdetails']['firstname'];
//     $lastname = $params['clientdetails']['lastname'];
//     $email = $params['clientdetails']['email'];
//     $address1 = $params['clientdetails']['address1'];
//     $address2 = $params['clientdetails']['address2'];
//     $city = $params['clientdetails']['city'];
//     $state = $params['clientdetails']['state'];
//     $postcode = $params['clientdetails']['postcode'];
//     $country = $params['clientdetails']['country'];
//     $phone = $params['clientdetails']['phonenumber'];
// 
//     // System Parameters
//     $companyName = $params['companyname'];
//     $systemUrl = $params['systemurl'];
//     $langPayNow = $params['langpaynow'];
//     $moduleDisplayName = $params['name'];
//     $moduleName = $params['paymentmethod'];
//     $whmcsVersion = $params['whmcsVersion'];
// 
//     // perform API call to initiate refund and interpret result
// 
//     return array(
//         // 'success' if successful, otherwise 'declined', 'error' for failure
//         'status' => 'success',
//         // Data to be recorded in the gateway log - can be a string or array
//         'rawdata' => $responseData,
//         // Unique Transaction ID for the refund transaction
//         'transid' => $refundTransactionId,
//         // Optional fee amount for the fee value refunded
//         'fees' => $feeAmount,
//     );
// }

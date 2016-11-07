<?php
/**
 * WHMCS Sample Payment Callback File
 *
 * This sample file demonstrates how a payment gateway callback should be
 * handled within WHMCS.
 *
 * It demonstrates verifying that the payment gateway module is active,
 * validating an Invoice ID, checking for the existence of a Transaction ID,
 * Logging the Transaction for debugging and Adding Payment to an Invoice.
 *
 * For more information, please refer to the online documentation.
 *
 * @see http://docs.whmcs.com/Gateway_Module_Developer_Docs
 *
 * @copyright Copyright (c) WHMCS Limited 2015
 * @license http://www.whmcs.com/license/ WHMCS Eula
 */

// Require libraries needed for gateway module functions.
require_once __DIR__ . '/../../../init.php';
require_once __DIR__ . '/../../../includes/gatewayfunctions.php';
require_once __DIR__ . '/../../../includes/invoicefunctions.php';
require_once(dirname(__FILE__).'/../paybox/paybox.php');

// Detect module name from filename.
$gatewayModuleName = basename(__FILE__, '.php');

// Fetch gateway configuration parameters.
$gatewayParams = getGatewayVariables($gatewayModuleName);

// Die if module is not active.
if (!$gatewayParams['type']) {
    die("Module Not Activated");
}

$transactionStatus = "OK";
$ORIG_REQUEST = $_REQUEST;

$pg_sig = $_REQUEST['pg_sig'];
unset($_REQUEST['pg_sig']);
$check_sig = PayBox::pg_sig('payboxkz.php', $_REQUEST, $gatewayParams['secret_key']);

if($pg_sig != $check_sig) {
    $transactionStatus = "Fail, Bad signature";
    logTransaction($gatewayParams['name'], print_r($_REQUEST, true)."\n\nsig1: {$pg_sig}\nsig2: {$check_sig}", $transactionStatus);
    exit;
}

$invoiceId = $_REQUEST['pg_order_id'];
$transactionId = $_REQUEST['pg_payment_id'];
$paymentAmount = $_REQUEST['pg_amount'];

/**
 * Validate Callback Invoice ID.
 *
 * Checks invoice ID is a valid invoice number. Note it will count an
 * invoice in any status as valid.
 *
 * Performs a die upon encountering an invalid Invoice ID.
 *
 * Returns a normalised invoice ID.
 */
$invoiceId = checkCbInvoiceID($invoiceId, $gatewayParams['name']);

/**
 * Check Callback Transaction ID.
 *
 * Performs a check for any existing transactions with the same given
 * transaction number.
 *
 * Performs a die upon encountering a duplicate.
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
$success = ($_REQUEST['pg_result'] == 1);
if ($success) {
    logTransaction($gatewayParams['name'], $ORIG_REQUEST, $transactionStatus);

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
    addInvoicePayment(
        $invoiceId,
        $transactionId,
        $paymentAmount,
        0,
        $gatewayModuleName
    );

    $params = [
        'pg_salt' => uniqid(),
        'pg_status' => 'ok',
        'pg_description' => 'Счет успешно оплачен',
    ];
    $params['pg_sig'] = PayBox::pg_sig('payboxkz.php', $params, $gatewayParams['secret_key']);

    echo "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n<response><pg_salt>{$params['pg_salt']}</pg_salt><pg_status>{$params['pg_status']}</pg_status><pg_description>{$params['pg_description']}</pg_description><pg_sig>{$params['pg_sig']}</pg_sig></response>";

} else {
    logTransaction($gatewayParams['name'], $ORIG_REQUEST, 'Failed transaction from PayBox');
}

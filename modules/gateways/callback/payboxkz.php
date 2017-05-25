<?php

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

$invoiceId = checkCbInvoiceID($invoiceId, $gatewayParams['name']);

checkCbTransID($transactionId);

$success = ($_REQUEST['pg_result'] == 1);
if ($success) {
    logTransaction($gatewayParams['name'], $ORIG_REQUEST, $transactionStatus);

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

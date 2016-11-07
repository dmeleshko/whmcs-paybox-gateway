<?php
/**
 * WHMCS Sample Payment Gateway Module
 *
 * Payment Gateway modules allow you to integrate payment solutions with the
 * WHMCS platform.
 *
 * This sample file demonstrates how a payment gateway module for WHMCS should
 * be structured and all supported functionality it can contain.
 *
 * Within the module itself, all functions must be prefixed with the module
 * filename, followed by an underscore, and then the function name. For this
 * example file, the filename is "gatewaymodule" and therefore all functions
 * begin "gatewaymodule_".
 *
 * If your module or third party API does not support a given function, you
 * should not define that function within your module. Only the _config
 * function is required.
 *
 * For more information, please refer to the online documentation.
 *
 * @see http://docs.whmcs.com/Gateway_Module_Developer_Docs
 *
 * @copyright Copyright (c) WHMCS Limited 2015
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
 * @see http://docs.whmcs.com/Gateway_Module_Meta_Data_Parameters
 *
 * @return array
 */
function payboxkz_MetaData()
{
    return array(
        'DisplayName' => 'Paybox.kz Payment Gateway Module',
        'APIVersion' => '1.1', // Use API Version 1.1
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
 * @return array
 */
function payboxkz_config()
{
    return array(
        // the friendly display name for a payment gateway should be
        // defined here for backwards compatibility
        'FriendlyName' => array(
            'Type' => 'System',
            'Value' => 'Paybox.kz',
        ),
        // a text field type allows for single line text input
        'merchant_id' => array(
            'FriendlyName' => 'Merchant ID',
            'Type' => 'text',
            'Size' => '25',
            'Default' => '',
            'Description' => 'Введите здесь Merchant ID',
        ),
        // a text field type allows for single line text input
        'secret_key' => array(
            'FriendlyName' => 'Secret KEY',
            'Type' => 'password',
            'Size' => '25',
            'Default' => '',
            'Description' => 'Secret KEY',
        ),
        // a password field type allows for masked text input
        'entry_url' => array(
            'FriendlyName' => 'Paybox Entry URL',
            'Type' => 'text',
            'Size' => '25',
            'Default' => 'https://www.paybox.kz/payment.php',
            'Description' => 'Paybox Entry URL',
        ),
        // the yesno field type displays a single checkbox option
        'testMode' => array(
            'FriendlyName' => 'Тестовый режим',
            'Type' => 'yesno',
            'Description' => 'Отметьте, чтобы включить тестовый режим',
        ),
        // the textarea field type allows for multi-line text input
        'description' => array(
            'FriendlyName' => 'Описание товара',
            'Type' => 'textarea',
            'Rows' => '3',
            'Cols' => '60',
            'Description' => 'Описание товара, которое отправляется в Paybox при платеже',
        ),
        // the dropdown field type renders a select menu of options
        'result_url' => array(
            'FriendlyName' => 'Result URL',
            'Type' => 'text',
            'Description' => 'Оповещение от Paybox.kz о результатах платежа',
        ),        
    );
}

/**
 * Payment link.
 *
 * Required by third party payment gateway modules only.
 *
 * Defines the HTML output displayed on an invoice. Typically consists of an
 * HTML form that will take the user to the payment gateway endpoint.
 *
 * @param array $params Payment Gateway Module Parameters
 *
 * @see http://docs.whmcs.com/Payment_Gateway_Module_Parameters
 *
 * @return string
 */
function payboxkz_link($params)
{

    $postfields = [
        'pg_merchant_id' => $params['merchant_id'],
        'pg_order_id' => $params['invoiceid'],
        'pg_amount' => $params['amount'],
        'pg_result_url' => $params['systemurl'] . '/modules/gateways/callback/' . $params['paymentmethod'] . '.php',
        'pg_request_method' => 'POST',
        'pg_success_url' => $params['success_url']?$params['success_url']:$params['returnurl'],
        'pg_success_url_method' => 'POST',
        'pg_failure_url' => $params['failure_url']?$params['failure_url']:$params['returnurl'],
        'pg_failure_url_method' => 'POST',
        'pg_site_url' => $params['site_url']?$params['site_url']:$params['systemurl'],
        'pg_description' => $params['description'],
        'pg_user_contact_email' => $params['clientdetails']['email'],
        'pg_salt' => uniqid(),
    ];
    require_once(dirname(__FILE__).'/paybox/paybox.php');
    $postfields['pg_sig'] = PayBox::pg_sig('payment.php', $postfields, $params['secret_key']);

    $htmlOutput = '<form method="post" action="' . $params['entry_url'] . '">';
    foreach ($postfields as $k => $v) {
        $htmlOutput .= '<input type="hidden" name="' . $k . '" value="' .$v . '" />';
    }
    $htmlOutput .= '<input type="submit" value="Оплатить на сайте Paybox.kz" />';
    $htmlOutput .= '</form>';

    return $htmlOutput;
}

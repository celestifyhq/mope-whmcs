<?php

require_once __DIR__ . '/../../../init.php';

App::load_function("clientarea");
App::load_function('gateway');
App::load_function('invoice');

// Detect module name from filename.
$gatewayModuleName = basename(__FILE__, '.php');

// Fetch gateway configuration parameters.
$gatewayParams = getGatewayVariables($gatewayModuleName);

// Verify the module is active.
if (!$gatewayParams['type']) {
    die("Module Not Activated");
}

// init Mopé client
$client = WHMCS\Module\Gateway\Mope\Client::factory($gatewayParams["testMode"] === "on" ? $gatewayParams["testApiKey"] : $gatewayParams["apiKey"], $gatewayParams["testMode"]);

// Retrieve data returned in redirect
$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';
$invoiceId = isset($_REQUEST['invoice_id']) ? $_REQUEST['invoice_id'] : '';
$amount = isset($_REQUEST['amount']) ? $_REQUEST['amount'] : '';
$currency = isset($_REQUEST['currency']) ? $_REQUEST['currency'] : '';
$fees = isset($_REQUEST['fees']) ? $_REQUEST['fees'] : '';
$verificationHash = isset($_REQUEST['verification_hash']) ? $_REQUEST['verification_hash'] : '';
$redirectUrl = isset($_REQUEST['redirect_url']) ? htmlspecialchars_decode($_REQUEST['redirect_url']) : '';

// Validate Verification Hash.
$comparisonHash = sha1(
    implode('|', [
        $gatewayParams["testMode"] === "on" ? $gatewayParams["testApiKey"] : $gatewayParams["apiKey"],
        $invoiceId,
    ])
);
if ( $verificationHash !== $comparisonHash ) {
    logTransaction($gatewayParams['paymentmethod'], $_REQUEST, "Invalid Hash");
    die('Invalid hash.');
}

// Validate invoice id received is valid.
$invoiceId = checkCbInvoiceID($invoiceId, $gatewayParams['paymentmethod']);
try {
    $invoice = WHMCS\Billing\Invoice::with("client", "client.currencyrel")->findOrFail($invoiceId);
} catch (Exception $e) {
    logTransaction($gatewayParams["paymentmethod"], array("whmcs_invoice_id" => $invoiceId), "Invoice ID Not Found");
    header("HTTP/1.1 200 OK");
    header("Status: 200 OK");
    WHMCS\Terminus::getInstance()->doExit();
}

if ($action == 'payment') {

    try {

        // Create a payment request
        $mope_order_params = array(
            "description" => "Invoice #" . $invoiceId,
            "amount" => $amount,
            "currency" => $currency,
            "order_id" => $invoiceId,
            "redirect_url" => $redirectUrl
        );

        $mope_order_response = json_decode($client->post("shop/payment_request", ["json" => $mope_order_params]));

        // log to transaction history as incomplete payment
        $history = WHMCS\Billing\Payment\Transaction\History::firstOrNew(array("invoice_id" => $invoiceId, "gateway" => $gatewayParams["paymentmethod"], "transaction_id" => $mope_order_response->id));
        $history->remoteStatus = "Payment Request Created";
        $history->completed = false;
        $history->additionalInformation = json_decode(json_encode($mope_order_response), true);
        $history->save();
        
        // log to gateway log as payment request was created
        logTransaction($gatewayParams['paymentmethod'], array_merge((array) $mope_order_response, array("whmcs_invoice_id" => $invoiceId)), "Payment Request Created");
        
        // redirect to mope payment page
        header("Location: " . $mope_order_response->url);
    }
    catch (Exception $e) {

        // Log to gateway log as unsuccessful.
        logTransaction($gatewayParams['paymentmethod'], $mope_order_response, $e->getMessage());

        // Redirect to the invoice with payment failed notice.
        callback3DSecureRedirect($invoiceId, false);
    }
}

if ($action == 'confirm') {

    $transaction_history = WHMCS\Billing\Payment\Transaction\History::where("gateway", $gatewayParams['paymentmethod'])->where("invoice_id", $invoiceId)->orderBy("id", "desc")->first();
    if(!empty($transaction_history) && $transaction_history->completed == false) {

        // Prevent double confirming a transaction
        checkCbTransID($transaction_history->transaction_id);
        
        try  {
            // check and verify Mopé payment request status
            $mope_order_response = json_decode($client->get("shop/payment_request/$transaction_history->transaction_id"));
            
            if ($mope_order_response->status == 'paid') {

                // Log to gateway log as successful.
                logTransaction($gatewayParams['paymentmethod'], $mope_order_response, "Success");

                // Apply payment to the invoice.
                addInvoicePayment($invoiceId, $mope_order_response->id, $mope_order_response->amount / 100, $fees, $gatewayModuleName);
                
                // Redirect to the invoice with payment successful notice.
                callback3DSecureRedirect($invoiceId, true);
            }
        }
        catch (Exception $e) {
            // Log to gateway log as unsuccessful.
            logTransaction($gatewayParams['paymentmethod'], $mope_order_response, $e->getMessage());

            // Redirect to the invoice with payment failed notice.
            callback3DSecureRedirect($invoiceId, false);
        }
    }
}
<?php

namespace WHMCS\Module\Gateway\Mope;

class Mope
{
    const MODULE_VERSION = "1.0.0"; 
    const SUPPORTED_CURRENCIES = array("SRD", "USD", "EUR");

    static function GetLatestVersion() {
        $client = new \GuzzleHttp\Client();
        $response = $client->get("https://store.celestify.com/wp-json/acf/v3/posts/732/latest_version");
        if ($response->getStatusCode() >= 400) {
            $json = json_decode($response->getBody());
            if ($json === null) {
                $msg = "Malformed response received from server";
                throw new \Exception($msg, $response);
            }
            $api_response = new Api\Response($response);
            $message = $api_response->body->error->message;
            foreach ($api_response->body->error->errors as $error) {
                $message .= " - " . $error->field . " " . $error->message;
            }
            throw new \WHMCS\Module\Gateway\Mope\Exception\ApiException($message, $response->getStatusCode());
        }
        return json_decode($response->getBody());        
    }

    static function GetVersionDescription() {
        $latest_version = "UNKNOWN";
        try {
            $latest_version = Mope::GetLatestVersion()->latest_version;
        }
        catch(\Exception $e) {
            $latest_version = "UNKNOWN";
        }
        if ($latest_version == "UNKNOWN") {
            $html = Mope::MODULE_VERSION . "
            <div class=\"alert alert-warning\" style=\"margin-bottom: 0;\">
                Unable to check for the latest version, try again later. If the problem persists, please <a target=\"_blank\" href=\"https://store.celestify.com/my-account/submit-ticket/\" class=\"alert-link autoLinked\">contact support</a>.
            </div>
            ";
        }
        else if (Mope::MODULE_VERSION == $latest_version) {
            $html = Mope::MODULE_VERSION . "
            <div class=\"alert alert-success\" style=\"margin-bottom: 0;\">
                This is the latest version.
            </div>
            ";
        }
        else {
            $html = Mope::MODULE_VERSION . "
            <div class=\"alert alert-warning\" style=\"margin-bottom: 0;\">
                This version is out of the date. Please upgrade to version 
                <a target=\"_blank\" href=\"https://store.celestify.com/product/mope-whmcs/\" class=\"alert-link autoLinked\">"
                    . $latest_version . 
                "</a> to stay up-to-date.
            </div>";
        }
        return $html;
    }

    static function PreviewPaymentButton($paymentButtonColour) {        
        $paymentButton = "/modules/gateways/mope/assets/button_" . strtolower($paymentButtonColour) . "_large.png";        
        $assetHelper = \DI::make("asset");
        $buttonUrl = $assetHelper->getWebRoot() . $paymentButton;
        return "<img src=\"" . $buttonUrl . "\" alt=\"Payment Button Preview\" />";
    }
}

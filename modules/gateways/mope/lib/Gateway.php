<?php

namespace WHMCS\Module\Gateway\Mope;

class Gateway
{

    protected $displayName = "Betaal met Mopé";

    public function getDisplayName()
    {
        return $this->displayName;
    }

    public function getMetaData()
    {
        return array(
            'DisplayName' => $this->getDisplayName(),
            'APIVersion' => '1.1', // Use API Version 1.1
        );
    }

    public function config(array $params = [])
    {

        $return = array( 
            "FriendlyName" => array( 
                "Type" => "System", 
                "Value" => $this->getDisplayName() 
            ),
            "moduleVersion" => array( 
                "FriendlyName" => "Module Version", 
                "Type" => "info", 
                "Description" => Mope::GetVersionDescription()
            ),
            "testApiKey" => array( 
                "FriendlyName" => "Test API Key", 
                "Type" => "text", 
                "Size" => "50", 
            ),
            "apiKey" => array( 
                "FriendlyName" => "Live API Key", 
                "Type" => "text", 
                "Size" => "50", 
            ),
            "testMode" => array( 
                "FriendlyName" => "Use Test Mode", 
                "Type" => "yesno" 
            ),
            'paymentButton' => array(
                'FriendlyName' => 'Payment Button Colour',
                'Type' => 'dropdown',
                'Options' => array(
                    'Black' => 'Black',
                    'Blue' => 'Blue',
                    'Orange' => 'Orange',
                    'White' => 'White',
                ),
                'Description' => Mope::PreviewPaymentButton($params['paymentButton']),
            ),
        );

        $currencies = \WHMCS\Billing\Currency::all()->pluck("code");
        $usageNotes = array();
        foreach ($currencies as $currencyCode) {
            if (!in_array($currencyCode, Mope::SUPPORTED_CURRENCIES)) {
                $usageNotes[] = "<strong>Unsupported Currencies.</strong> You have one or more " . "currencies configured that are not supported by Mopé. Invoices using " . "currencies Mopé does not support will be unable to be paid using Mopé.";
                break;
            }
        }
        $systemUrl = \App::getSystemURL();
        if (substr($systemUrl, 0, 5) != "https") {
            $usageNotes[] = "<strong>Mopé requires an HTTPS secured connection for the WHMCS installation.</strong> " . "Your current WHMCS System URL setting does not begin with https and will be rejected. " . "Please add an SSL Certificate to your WHMCS domain to use Mopé. ";
        }
        if ($usageNotes) {
            $return["UsageNotes"] = array("Type" => "System", "Value" => implode("<br>", $usageNotes));
        }
        
        return $return;
    }

    public function viewPaymentButton(array $params = array())
    {
        if (!empty($params["currency"]) && !in_array($params["currency"], Mope::SUPPORTED_CURRENCIES)) {
            return "<div class=\"alert alert-danger\">" . "Payment Method Unavailable - Please select an alternate payment method" . "</div>";
        }
        
        // calculate verificationHash
        $verificationHash = sha1(
            implode('|', [
                $params["testMode"] === "on" ? $params["testApiKey"] : $params["apiKey"],
                $params["invoiceid"],
            ])
        );
        
        // setup values
        $values = array(
            "action" => "payment",
            "testMode" => $params["testMode"] === "on" ? "sandbox" : "prod",
            "redirect_url" => $params["systemurl"] . "modules/gateways/callback/" . $params['paymentmethod'] . ".php" . "?invoice_id=" . $params["invoiceid"] . "&verification_hash=" . $verificationHash . "&action=confirm",
            "cancel_url" => $params["systemurl"] . "modules/gateways/callback/" . $params['paymentmethod'] . ".php" . "?invoice_id=" . $params["invoiceid"] . "&verification_hash=" . $verificationHash . "&action=cancel",
            "paymentmethod" => $params["paymentmethod"],
            "amount" => $params["amount"] * 100, 
            "currency" => $params["currency"],
            "paymentButton" => "/modules/gateways/mope/assets/button_" . strtolower($params["paymentButton"]) . "_large.png",
        );

        $assetHelper = \DI::make("asset");
        $buttonUrl = $assetHelper->getWebRoot() . $values["paymentButton"];
        $buttonMarkup = "<input type=\"image\" src=\"$buttonUrl\" value=\"Betaal met Mopé\" />";
        
        $link = "<form action=\"{$values["redirect_url"]}\" method=\"POST\">
            <input type=\"hidden\" name=\"action\" value=\"{$values["action"]}\">
            <input type=\"hidden\" name=\"invoice_id\" value=\"{$params["invoiceid"]}\">
            <input type=\"hidden\" name=\"amount\" value=\"{$values["amount"]}\">
            <input type=\"hidden\" name=\"currency\" value=\"{$values["currency"]}\">
            <input type=\"hidden\" name=\"redirect_url\" value=\"{$values["redirect_url"]}\">
            {$buttonMarkup}
        </form>";

        // hide 3d secure iframe
        $link .= '
        <style type="text/css">
            iframe[name="ccframe"] {
                display:none;
            }
            #mope-field > iframe { 
                height: unset !important;
            }
        </style>';

        return $link;
    }
}
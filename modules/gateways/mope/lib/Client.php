<?php

namespace WHMCS\Module\Gateway\Mope;

class Client
{
    protected $testMode = false;
    protected $apiKey = "";
    const URLS = array("test" => "https://api.mope.sr/api/", "live" => "https://api.mope.sr/api/");
    const API_VERSION = "1.0";

    public function __construct($apiKey, $testMode)
    {
        $this->setTestMode($testMode == "on");
        $this->setApiKey($apiKey);
    }

    public static function factory($apiKey, $testMode)
    {
        $client = new static($apiKey, $testMode == "on");
        return new Api\Client($client->getDefaultOptions());
    }

    protected function getUrl()
    {
        $type = "live";
        if ($this->istest()) {
            $type = "test";
        }
        return self::URLS[$type];
    }

    protected function istest()
    {
        return $this->testMode;
    }

    protected function getUserAgent()
    {
        $uAgent = array();
        $uAgent[] = "whmcs/" . \App::getVersion()->getMajor();
        $uAgent[] = "Mopé Payment Gateway/" . self::API_VERSION;
        $uAgent[] = "GuzzleHttp/" . \GuzzleHttp\Client::MAJOR_VERSION;
        $uAgent[] = "php/" . phpversion();
        if (extension_loaded("curl") && function_exists("curl_version")) {
            $curlInfo = curl_version();
            $uAgent[] = "curl/" . $curlInfo["version"];
            $uAgent[] = "curl/" . $curlInfo["host"];
        }
        return implode(" ", $uAgent);
    }

    protected function setTestMode($test)
    {
        $this->testMode = $test;
    }

    protected function setApiKey($apiKey)
    {
        $this->apiKey = $apiKey;
    }

    protected function getApiKey()
    {
        return $this->apiKey;
    }

    protected function getDefaultOptions()
    {
        return array(
            "base_uri" => $this->getUrl(), 
            "headers" => array(
                // "Mope-Version" => self::API_VERSION,
                "User-Agent" => $this->getUserAgent(),
                "Accept" => "application/json", 
                "Content-Type" => "application/json", 
                "Authorization" => "Bearer " . $this->getApiKey(),
            )
        );
    }
}

?>
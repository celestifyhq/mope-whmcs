<?php

namespace WHMCS\Module\Gateway\Mope\Exception;

class ApiException extends \Exception
{
    public function __construct($message, $code = 0, $previous = null) {

        // make sure everything is assigned properly
        if (!$message) {
            switch($code) {
                case 400:
                    $message = "Bad Request -- Your request is invalid.";
                    break;
                case 401:
                	$message = 	"Unauthorized -- Your access token is wrong.";
                    break;
                case 403:
                	$message = 	"Forbidden -- Access to the requested resource or action is forbidden.";
                    break;
                case 404:
                	$message = 	"Not Found -- The requested resource could not be found.";
                    break;
                case 405:
                	$message = 	"Method Not Allowed -- You tried to access an endpoint with an invalid method.";
                    break;
                case 406:
                	$message = 	"Not Acceptable -- You requested a format that isn't JSON.";
                    break;
                case 429:
                	$message = 	"Too Many Requests -- You're sending too many requests.";
                    break;
                case 500:
                	$message = 	"Internal Server Error -- We had a problem with our server. Try again later.";
                    break;
                case 503:
                	$message = 	"Service Unavailable -- We're temporarily offline for maintenance. Please try again later.";
                    break;
            }
            throw new \Exception($message, $code, $previous);
        }
    }
}

?>
<?php

namespace WHMCS\Module\Gateway\Mope\Api;

class Client
{

  protected $client = NULL;
  protected $options = array();
  const API_VERSION = "1.0";

  public function __construct(array $options)
  {
      $this->options = $options;
      $this->client = $this->getClient();
  }

  protected function getClient()
  {
      if (is_null($this->client)) {
          return new \GuzzleHttp\Client($this->options);
      }
      return $this->client;
  }

  public function get($path, $params = array())
  {
      if (is_array($params) && array_key_exists("query", $params)) {
          $params["query"] = $this->castBooleanValuesToStrings($params["query"]);
      }
      $response = $this->getClient()->get($path, $params);
      $this->handleErrors($response);
      return $response->getBody();
  }

  public function put($path, $params)
  {
      $response = $this->getClient()->put($path, $params);
      $this->handleErrors($response);
      return $response->getBody();
  }

  public function post($path, $params)
  {
      $idempotencyKey = uniqid("", true);
      $paramsWithHeaders = array("headers" => array("Idempotency-Key" => $idempotencyKey));
      $params = array_merge($params, $paramsWithHeaders);
      $response = $this->getClient()->post($path, $params);
      $this->handleErrors($response);
      return $response->getBody();
  }

  public function delete($path, $params = array())
  {
      $response = $this->getClient()->delete($path, $params);
      $this->handleErrors($response);
      return $response->getStatusCode();
  }

  public function handleErrors($response)
  {
        if ($response->getStatusCode() >= 400) {
            $json = json_decode($response->getBody());
            if ($json === null) {
                $msg = "Malformed response received from server";
                throw new \WHMCS\Module\Gateway\Mope\Exception\MalformedResponseException($msg, $response);
            }
            $api_response = new Response($response);
            $message = $api_response->body->error->message;
            foreach ($api_response->body->error->errors as $error) {
                $message .= " - " . $error->field . " " . $error->message;
            }
            throw new \WHMCS\Module\Gateway\Mope\Exception\ApiException($message, $response->getStatusCode());
        }
  }

  protected function castBooleanValuesToStrings($query)
  {
      return array_map(function ($value) {
          if ($value === true) {
              return "true";
          }
          if ($value === false) {
              return "false";
          }
          if (is_array($value)) {
              return $this->castBooleanValuesToStrings($value);
          }
          return $value;
      }, $query);
  }

}
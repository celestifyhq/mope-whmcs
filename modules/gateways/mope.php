<?php

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

use WHMCS\Module\Gateway\Mope\Gateway as MopeGateway;


function mope_MetaData()
{
    $gateway = new MopeGateway();
    return $gateway->getMetaData();
}

function mope_config(array $params = [])
{
    $gateway = new MopeGateway();
    return $gateway->config($params);
}

function mope_link($params)
{
    $gateway = new MopeGateway();
    return $gateway->viewPaymentButton($params);
}
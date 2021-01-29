<?php
/**
 * Created by PhpStorm.
 * User: houwei_public
 * Date: 2021/1/9
 * Time: 10:23
 */
echo __DIR__;die;
namespace Mqtt\Client;
use Mqtt\protocol\Mqtt;
echo Mqtt::PUBREL;die;
$config=[
    'client_id'=>'product_test',
    'username'=>'test',
    'password'=>'test',
];
$client=new Client($config);
$data=$client->connect();
var_dump($data);
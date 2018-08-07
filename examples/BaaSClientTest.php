<?php

require "../vendor/autoload.php";

use GXChain\BaaS\BaaSClient;

$config = [
    'url'  => [
        'fee'   => 'https://baas-developer.gxchain.cn/api/storage/provider/',
        'store' => 'https://baas-developer.gxchain.cn/api/storage/store/',
        'get'   => 'https://baas-developer.gxchain.cn/api/storage/data/',
    ],
    'base' => [
        'from'         => '1.2.XXX',
        'to'           => '1.2.241',
        'proxyAccount' => '1.2.241',
        'percent'      => 0,
        'assetId'      => '1.3.1',
        'privateKey'   => 'XXXXX',
    ],

];
$obj = new BaaSClient($config);

//store
$res = $obj->store('hello gxs php ssdk v1.0.1');
echo $res;

//storeGet
// $cid = 'Qmb3eECKK9rAJbG7BpFgGxeSFDH9nkie6XugxJJUqVQUUK';
// $res = $obj->storeGet($cid);
// echo $res;

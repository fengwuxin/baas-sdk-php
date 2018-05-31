<?php
namespace Gxchain\BaaS;

class Config
{
    public static $url = [
        'fee'   => 'https://baas-developer.gxchain.cn/api/storeFee/',
        'store' => 'https://baas-developer.gxchain.cn/api/store/',
        'get'   => 'https://baas-developer.gxchain.cn/api/data/',
    ];
    public static $base = [
        'from'         => '',
        'to'           => '1.2.60',
        'proxyAccount' => '1.2.60',
        'percent'      => 0,
        'assetId'      => '1.3.1',
        'privateKey'   => '',
    ];
}

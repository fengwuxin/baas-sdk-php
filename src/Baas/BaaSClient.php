<?php
namespace Gxchain\BaaS;

use Gxchain\BaaS\Config;

class BaaSClient
{
    public function store($str = '')
    {
        $configBase = Config::$base;
        $configUrl  = Config::$url;
        $toolObj    = new Tool();

        $time = $toolObj->getTime();

        $amount = $toolObj->getAmount($str);
        $memo   = md5($str);

        $byteFrom       = $toolObj->account($configBase['from']);
        $byteTo         = $toolObj->account($configBase['to']);
        $byteProxy      = $toolObj->account($configBase['proxyAccount']);
        $bytePercent    = $toolObj->percent($configBase['percent']);
        $byteAmount     = $toolObj->amount($amount, $configBase['assetId']);
        $byteTime       = $toolObj->dateTime($time);
        $byteMemo       = $toolObj->memo($memo);
        $byteMemoLength = $toolObj->memoLength(strlen($memo));
        $byteEmpty      = [0];

        $byteDataArr = [
            'byteFrom'       => $byteFrom,
            'byteTo'         => $byteTo,
            'byteProxy'      => $byteProxy,
            'bytePercent'    => $bytePercent,
            'byteAmount'     => $byteAmount,
            'byteTime'       => $byteTime,
            'byteMemo'       => $byteMemo,
            'byteMemoLength' => $byteMemoLength,
            'byteEmpty'      => $byteEmpty,
        ];

        $signatures = $toolObj->sign($byteDataArr, $configBase['privateKey']);

        $bassData = [
            'amount'        => $amount,
            'memo'          => $memo,
            'from'          => $configBase['from'],
            'expiration'    => $toolObj->getTime(),
            'to'            => $configBase['to'],
            'asset_id'      => $configBase['assetId'],
            'proxy_account' => $configBase['proxyAccount'],
            'percent'       => $configBase['percent'],
            'signatures'    => $signatures,
            'data'          => $str,
        ];

        $curlRes = $toolObj->curl($configUrl['store'], 'post', $bassData);
        return $curlRes;
    }

    public function storeGet($cid = '')
    {
        $configUrl = Config::$url;
        $toolObj   = new Tool();

        $curlRes = $toolObj->curl($configUrl['get'] . $cid);
        return $curlRes;
    }
}

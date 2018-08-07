<?php
namespace GXChain\BaaS;

use GXChain\Common\PrivateKey;
use GXChain\Common\Signature;
use GXChain\Http\RequestCore;
use GXChain\Http\ResponseCore;

class BaaSClient
{

    private $config;

    public function __construct($config)
    {
        $this->config = $config;
    }

    public function store($str = '')
    {
        $configBase = $this->config['base'];
        $configUrl  = $this->config['url'];

        $signature = new Signature();

        $expiration = time() + 30 * 60;

        $amount = $this->getAmount($str, $configBase['assetId']);
        $memo   = md5($str);

        $byteFrom       = $signature->account($configBase['from']);
        $byteTo         = $signature->account($configBase['to']);
        $byteProxy      = $signature->account($configBase['proxyAccount']);
        $bytePercent    = $signature->percent($configBase['percent']);
        $byteAmount     = $signature->amount($amount, $configBase['assetId']);
        $byteTime       = $signature->dateTime($expiration);
        $byteMemo       = $signature->memo($memo);
        $byteMemoLength = $signature->memoLength(strlen($memo));
        $byteEmpty      = [0];

        $byteArr = array_merge($byteFrom, $byteTo, $byteProxy, $byteAmount, $bytePercent, $byteMemoLength, $byteMemo, $byteTime, $byteEmpty);

        $signatures = PrivateKey::fromWif($configBase['privateKey'])->sign($byteArr);

        $bassData = [
            'amount'        => $amount,
            'memo'          => $memo,
            'from'          => $configBase['from'],
            'expiration'    => $expiration,
            'to'            => $configBase['to'],
            'asset_id'      => $configBase['assetId'],
            'proxy_account' => $configBase['proxyAccount'],
            'percent'       => $configBase['percent'],
            'signatures'    => $signatures,
            'data'          => $str,
        ];

        $request = new RequestCore($configUrl['store']);
        $request->set_method('POST');
        $request->set_body($bassData);
        $request->send_request();
        $res = new ResponseCore($request->get_response_header(), $request->get_response_body(), $request->get_response_code());

        $output = $res->body;

        return $output;
    }

    public function storeGet($cid = '')
    {
        $configUrl = $this->config['url'];

        $request = new RequestCore($configUrl['get'] . $cid);
        $request->set_method('GET');
        $request->send_request();
        $res    = new ResponseCore($request->get_response_header(), $request->get_response_body(), $request->get_response_code());
        $output = $res->body;

        return $output;
    }

    private function getAmount($str, $assetId)
    {
        $configUrl = $this->config['url'];

        $request = new RequestCore($configUrl['fee']);
        $request->set_method('GET');
        $request->send_request();
        $res         = new ResponseCore($request->get_response_header(), $request->get_response_body(), $request->get_response_code());
        $output      = $res->body;
        $arr         = json_decode($output, true);
        $feeKByteArr = $arr['data']['fees'];
        $feeKByte    = 0;
        if (!empty($feeKByteArr) && is_array($feeKByteArr)) {
            foreach ($feeKByteArr as $key => $value) {
                if ($value['asset_id'] == $assetId) {
                    $feeKByte = $value['fee_per_kbytes'];
                }
            }
        }
        $length = strlen($str);
        $f      = (int) ($length / 1024 * $feeKByte);
        return $length % 1024 == 0 ? $f : $f + $feeKByte;
    }
}

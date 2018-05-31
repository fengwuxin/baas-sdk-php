<?php
namespace Gxchain\BaaS;

use Gxchain\BaaS\Config;

class Tool
{

    private $time;

    public function __construct()
    {
        $this->time = time() + 8 * 60 * 60 + 60;
    }

    public function getTime()
    {
        return $this->time;
    }

    public function sign($byteDataArr, $privateKey)
    {
        $byteArr = array_merge($byteDataArr['byteFrom'], $byteDataArr['byteTo'], $byteDataArr['byteProxy'], $byteDataArr['byteAmount'], $byteDataArr['bytePercent'], $byteDataArr['byteMemoLength'], $byteDataArr['byteMemo'], $byteDataArr['byteTime'], $byteDataArr['byteEmpty']);
        $hex     = $this->signature($byteArr, $privateKey);

        $sigData = $this->hexToBytes($hex);
        while ((($sigData[1] & 128) != 0 || $sigData[1] == 0 || ($sigData[2] & 128) != 0 || ($sigData[33] & 128) != 0 || $sigData[33] == 0 || ($sigData[34] & 128) != 0)) {
            $this->time = $this->time + 1;
            $byteTime   = $this->dateTime($this->time);
            $byteArr    = array_merge($byteDataArr['byteFrom'], $byteDataArr['byteTo'], $byteDataArr['byteProxy'], $byteDataArr['byteAmount'], $byteDataArr['bytePercent'], $byteDataArr['byteMemoLength'], $byteDataArr['byteMemo'], $byteTime, $byteDataArr['byteEmpty']);
            $hex        = $this->signature($byteArr, $privateKey);
            $sigData    = $this->hexToBytes($hex);
        }
        return $hex;
    }

    public function getAmount($str)
    {
        $configUrl = Config::$url;
        $curlStr   = $this->curl($configUrl['fee']);
        $arr       = json_decode($curlStr, true);
        $feeKByte  = $arr['data']['price_per_kbyte'];
        $amount    = $this->calculateAmount($feeKByte, $str);
        return $amount;
    }

    public function dateTime($time)
    {
        $byte = $this->writeUnsignedSize($time);
        return $byte;
    }

    public function memo($memo)
    {
        $byte = unpack('c*', $memo);
        return $byte;
    }

    public function memoLength($length)
    {
        $byte   = [];
        $byte[] = ($length & 0xff);
        return $byte;
    }

    public function percent($percent)
    {
        $byte   = [];
        $byte[] = ($percent & 0xff);
        $byte[] = ($percent >> 8 & 0xff);
        return $byte;
    }

    public function amount($amount, $assetId)
    {
        $assetId = 1;
        $byte    = unpack("c*", pack("L*", $amount));
        for ($i = count($byte); $i < 8; $i++) {
            $byte[] = 0;
        }
        $byte[] = $assetId;
        return $byte;
    }

    public function account($accountid)
    {
        $arr    = explode(".", $accountid);
        $number = $arr[2];
        $byte   = $this->writeUnsignedVarLong($number);
        return $byte;
    }

    public function calculateAmount($feeKByte, $str)
    {
        $length = strlen($str);
        $f      = (int) ($length / 1024 * $feeKByte);
        return $length % 1024 == 0 ? $f : $f + $feeKByte;
    }

    public function curl($url, $method = '', $post = [], $returnHeaderInfo = false, $timeout = 60)
    {
        //Content-Type: multipart/form-data
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_TIMEOUT, $timeout); //设置超时时间,单位秒
        curl_setopt($curl, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 5.1; zh-CN; rv:1.9.2.8) Gecko/20100722 Firefox/3.6.8");
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        if ($method == 'post') {
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $post);
        }
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        $str      = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        unset($curl);
        if (!$str) {
            return false;
        }
        //返回头信息
        if ($returnHeaderInfo) {
            return array($httpCode, $str);
        }
        return $str;
    }

    private function signature($byteArr, $privateKey)
    {
        $binstr = '';
        foreach ($byteArr as $key => $value) {
            $binstr .= pack("c*", $value);
        }
        $context = secp256k1_context_create(SECP256K1_CONTEXT_SIGN | SECP256K1_CONTEXT_VERIFY);

        $msg32 = hash('sha256', $binstr, true);

        $arr = $this->decode($privateKey);

        $privateKeyBin = '';
        foreach ($arr as $key => $value) {
            $privateKeyBin .= pack("c*", $value);
        }

        $signature = '';
        secp256k1_ecdsa_sign_recoverable($context, $signature, $msg32, $privateKeyBin);
        $serialized = '';
        $recid      = 0;
        secp256k1_ecdsa_recoverable_signature_serialize_compact($context, $signature, $serialized, $recid);
        $hex     = bin2hex($serialized);
        $byteArr = unpack("c*", $serialized);
        array_unshift($byteArr, (27 + 4) & 0xff);
        $str = '';
        foreach ($byteArr as $key => $value) {
            $str .= pack("c*", $value);
        }
        $hex = bin2hex($str);
        return $hex;
    }

    private function writeUnsignedVarLong($number)
    {
        $byte = [];
        while (($number & -128) != 0) {
            $byte[] = $number & 127 | 128;
            $number = $this->uright($number, 7);
        }
        $byte[] = $number & 127;
        $str    = '';
        foreach ($byte as $key => $value) {
            $str .= pack("c*", $value);
        }
        $byte = unpack("c*", $str);
        return $byte;
    }

    private function uright($a, $n)
    {
        $c = 2147483647 >> ($n - 1);
        return $c & ($a >> $n);
    }

    private function writeUnsignedSize($val)
    {
        $byte   = array();
        $byte[] = ($val & 0xff);
        $byte[] = ($val >> 8 & 0xff);
        $byte[] = ($val >> 16 & 0xff);
        $byte[] = ($val >> 24 & 0xff);
        $str    = '';
        foreach ($byte as $key => $value) {
            $str .= pack("c*", $value);
        }
        $byte = unpack("c*", $str);
        return $byte;
    }

    private function decode($str)
    {
        $ALPHABET     = '123456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz';
        $ALPHABET_ARR = str_split($ALPHABET);

        $BASE         = count($ALPHABET_ARR);
        $LEADER       = $ALPHABET_ARR[0];
        $ALPHABET_MAP = [];
        for ($i = 0; $i < $BASE; $i++) {
            $ALPHABET_MAP[$ALPHABET[$i]] = $i;
        }

        $str_arr = str_split($str);
        $bytes   = [0];
        for ($i = 0; $i < count($str_arr); $i++) {
            $value = $ALPHABET_MAP[$str_arr[$i]];
            $carry = $value;
            for ($j = 0; $j < count($bytes); ++$j) {
                $carry += $bytes[$j] * $BASE;
                $bytes[$j] = $carry & 0xff;
                $carry >>= 8;
            }
            while ($carry > 0) {
                $bytes[] = $carry & 0xff;
                $carry >>= 8;
            }
        }
        for ($k = 0; $k === $LEADER && $k < count($str_arr) - 1; ++$k) {
            $bytes[] = 0;
        }
        $bytes = array_reverse($bytes);
        $bytes = array_slice($bytes, 0, -4);
        $bytes = array_slice($bytes, 1);
        return $bytes;
    }

    private function hexToBytes($hex)
    {
        $arr   = str_split($hex);
        $count = count($arr);
        $data  = [];
        for ($i = 0; $i < $count; $i += 2) {
            $a            = hexdec($arr[$i]);
            $b            = hexdec($arr[$i + 1]);
            $data[$i / 2] = ((($a << 4) + $b)) & 0xff;
        }
        $str = '';
        foreach ($data as $key => $value) {
            $str .= pack("c*", $value);
        }
        $byte = unpack("c*", $str);
        $byte = array_values($byte);
        return $byte;
    }
}

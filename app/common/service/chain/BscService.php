<?php

namespace app\common\service\chain;

use app\common\facade\OkLink;
use app\common\facade\Redis;
use EthereumRPC\EthereumRPC;
use Exception;
use GuzzleHttp\Client;
use Web3\Utils;
use Web3p\EthereumTx\Transaction;
use Web3p\EthereumUtil\Util;
use Web3p\EthereumWallet\Wallet;

/**
 * BSC基础服务
 * @time 2023/6/29
 */
class BscService
{
    //地址
    private $host;
    //端口
    private $port;
    //初始化
    public $geth;
    //浏览器地址
    private $scan_url = 'https://api.bscscan.com';

    /*
     * @params string $host  geth服务器ip
     * @params int $port geth服务器端口
     */
    public function __construct()
    {
        $this->host = env('network.bsc_host', 'bsc-dataseed.binance.org');
        $this->port = env('network.bsc_port') ?: null;
        $this->geth = new EthereumRPC($this->host, $this->port);
    }

    /**
     * 实例化
     * @return BscService
     * @author Bin
     * @time 2023/8/11
     */
    public static function instance()
    {
        return new self();
    }

    /*
     * 发送jsonapi请求
     * @params string $command 命令名称
     * @params array $params 变量参数
     * @params string $method 请求方式
     */
    public function sendCommand($command,$params=[],$method = 'POST'){
        if( empty($params) ){ $params=[]; }
        try {
            $result = $this->geth->jsonRPC($command,'',$params,$method);
            $array['code'] = 200;
            return array_merge_recursive($array,$result->array());
        } catch (Exception $e) {
            return ['msg'=>$e->getMessage(),'code'=>$e->getCode()];
        }

    }

    /**
     * 查询账户余额
     * @param string $address 查询的钱包地址
     * @param string $contract 代币合约地址为空不查询代币
     * @return array
     * @time 2023/6/30
     */
    public function getBalance(string $address, string $contract = '')
    {
        if($contract) {
            $method_hash = '0x70a08231';

            $method_param1_hex = str_pad(substr($address, 2), 64, '0', STR_PAD_LEFT);

            $data = $method_hash . $method_param1_hex;
            $params['from'] = $address;
            $params['to'] = $contract;
            $params['data'] = $data;
            $result = $this->sendCommand('eth_call',[$params, "latest"]);
        }else{
            $result = $this->sendCommand('eth_getBalance',[$address, "latest"]);
        }

        if($result['result']) {
            $result['result'] = hexdec($result['result']) / (pow(10, 18));
        }

        return $result;

    }

    /*
     * 估算gas
     * @params array $params[from,to,data] 查询转账手续费
     */
    public function getestimateGas($params){
        $result = $this->sendCommand('eth_estimateGas',[$params]);
        if(isset($result['result']) && !empty($result['result'])){
            return $result['result'];
        }else{
            return $result;
        }

    }

    /*
     * 获取gas价格
     */
    public function getGasPrice(){
        $result = $this->sendCommand('eth_gasPrice',[]);
        return $result['result'] ?? 0;
    }

    /*
     * php自带的dechex无法把大整型转换为十六进制
     * @params string $decimal 需转换的16进制数
    */
    public function bcDecHex($decimal)
    {
        $result = [];
        while ($decimal != 0) {
            $mod = $decimal % 16;
            $decimal = floor($decimal / 16);
            array_push($result, dechex($mod));
        }
        return join(array_reverse($result));
    }

    /**
     * 查询节点
     * @param $address
     * @return array
     * @time 2023/6/30
     */
    public function getTransactionCount($address){
        $result = $this->sendCommand('eth_getTransactionCount',[$address,'latest']);
        return $result;
    }

    /**
     * 查询交易
     * @param $hash
     * @return array
     * @time 2023/6/30
     */
    public function getTransactionReceipt($hash){
        $result = $this->sendCommand('eth_getTransactionReceipt',[$hash]);
        // echo "<pre>";var_dump(  $result  );die;
        return $result;
    }

    //获取手续费
    public function getServiceCharge($from ,$to, $value, $contract = '', $password = ''){
        //解锁账号
        // $result = $this->sendCommand('personal_unlockAccount',[$from,$password,100]);
//        $value = bcpow(10, 18) * $value;
        if(!empty($contract)) {
            $method_hash = '0xa9059cbb';
            $method_param1_hex =str_pad(substr($to, 2), 64, '0', STR_PAD_LEFT);
            $method_param2_hex = str_pad(strval($this->bcDecHex($value)), 64, '0', STR_PAD_LEFT);
            $data = $method_hash . $method_param1_hex . $method_param2_hex;
            $params = ['from' => $from, 'to' => $contract, 'data' => $data];
            // print_r($params);
            $params['gas'] = $this->getestimateGas($params);
            $params['gasPrice'] = $this->getGasPrice();

            $result = bcmul(hexdec($params['gas']), ( bcdiv(hexdec($params['gasPrice']), bcpow(10, 18), 18) ), 18);
        }else{
            $params = ['from' => $from, 'to' => $to,'data'=>''];
            $params['gas'] = $this->getestimateGas($params);
            $params['gasPrice'] = $this->getGasPrice();
            $result = bcmul(hexdec($params['gas']), ( bcdiv(hexdec($params['gasPrice']), bcpow(10, 18), 18) ), 18)
; //手续费
        }
        return $result;
    }

    public function hexDec(string $hex): string
    {
        $dec = 0;
        $len = strlen($hex);
        for ($i = 1; $i <= $len; $i++) {
            $dec = bcadd($dec, bcmul(strval(hexdec($hex[$i - 1])), bcpow('16', strval($len - $i))));
        }
        return $dec;
    }

    public function decHex($dec): string
    {
        $last = bcmod($dec, 16);
        $remain = bcdiv(bcsub($dec, $last), 16);

        if ($remain == 0) {
            return dechex($last);
        } else {
            return self::DecHex($remain) . dechex($last);
        }
    }

    /**
     * 转账
     * @param string $from
     * @param string $to
     * @param float $value
     * @param string $privateKey
     * @param string $contract
     * @return array|mixed
     * @author hebin
     * @time 2023/6/30
     */
    public function transferRaw(string $from ,string $to, string $value, string $privateKey, string $contract = '')
    {
        if(!empty($contract)) {
            $method_hash = '0xa9059cbb';
            $method_param1_hex =str_pad(substr($to, 2), 64, '0', STR_PAD_LEFT);
            $method_param2_hex = str_pad(strval($this->bcDecHex(bcmul($value, bcpow(10, 18)))), 64, '0', STR_PAD_LEFT);
            $data = $method_hash . $method_param1_hex . $method_param2_hex;
            $params = ['from' => $from, 'to' => $contract, 'data' => $data];

            $params['gas'] = $this->getestimateGas($params);
            if(!$params['gas']){
                return $params['gas'];
            }

            $params['gasPrice'] = $this->getGasPrice();

            if(!$params['gasPrice']){
                return $params['gasPrice'];
            }
            $params['value'] = '0x0';
            $nonces = $this->getTransactionCount($from);
            $params['nonce'] = $nonces['result'];
            $params['chainId'] = 56;

            //  报错信息
            if( isset( $params['gas']['code'] ) ){
                $return_arr['code'] = $params['gas']['code'];
                $return_arr['msg'] = $params['gas']['msg'];
                $return_arr['hash_address'] = false;
                $return_arr['type'] = 1;
                return $return_arr;
            }

            $gasprice = intval(hexdec($params['gasPrice']));
            $params['gasPrice'] = '0x'.$this->bcDecHex($gasprice);
            $return_arr['fee'] = hexdec($params['gas']) * hexdec($params['gasPrice'])/ bcpow(10, 18); //手续费

            $transaction = new Transaction($params);
            $signedTransaction = '0x' . $transaction->sign($privateKey);
            $result = $this->sendCommand('eth_sendRawTransaction', [$signedTransaction]);
        }else{
            $params = ['from' => $from, 'to' => $to,'data'=>''];
            $params['gas'] = $this->getestimateGas($params);
            $params['gasPrice'] = $this->getGasPrice();
            $gasprice = intval(hexdec($params['gasPrice']));
            $params['gasPrice'] = '0x'.$this->bcDecHex($gasprice);
            $params['value'] = '0x'.$this->bcDecHex($value * bcpow(10, 18));
            $nonces = $this->getTransactionCount($from);
            $params['nonce'] = $nonces['result'];
            $params['chainId'] = 56;
            //  报错信息
            if( isset( $params['gas']['code'] ) ){
                $result['result'] = false;
                $return_arr['code'] = $params['gas']['code'];
                $result['msg'] = $params['gas']['msg'];
                $return_arr['msg'] = $params['gas']['msg'];
                $return_arr['hash_address'] = $result['result'];
                $return_arr['type'] = 2;
                return $return_arr;
            }

            $return_arr['fee'] = hexdec($params['gas']) * hexdec($params['gasPrice'])/ (pow(10, 18)); //手续费

            $transaction = new Transaction($params);
            $signedTransaction = '0x'.$transaction->sign($privateKey);
            $result = $this->sendCommand('eth_sendRawTransaction', [$signedTransaction]);
        }
        $return_arr['hash_address'] = $result['result'] ?? '';
        $return_arr['msg'] = $result['msg'] ?? '';
        return $return_arr;
    }

    public function addDecode($string)
    {
        $strings = $string;
        $newstr = substr($strings, 0,strlen($strings)-4);
        $newstr1 = substr($newstr,-20);
        $newstr2 = str_replace($newstr1,'',$newstr);
        return $newstr1.$newstr2;
    }

    /**
     * 创建钱包
     * @return array
     * @time 2023/6/30
     */
    public function createWallet()
    {
        $wallet = new Wallet();
        $result = $wallet->generate(12);
        return [
            'public_key' => $result->getPublicKey(),
            'address' => $result->getAddress(),
            'private_key' => $result->getPrivateKey(),
            'mnemonic' => $result->getMnemonic()
        ];
    }

    /**
     * 检测是否合法地址
     * @param string $address
     * @return bool
     * @author Bin
     * @time 2023/7/8
     */
    public function isAddress(string $address)
    {
        return Utils::isAddress($address);
    }

    /**
     * 获取交易列表
     * @param string $address
     * @param int $start_block
     * @return array|false|string
     * @author Bin
     * @time 2023/7/16
     */
    public function getTxList(string $address, int $start_block)
    {
        $api_key = $this->getApiKey();
        $url = $this->scan_url . "/api?module=account&action=txlist&address={$address}&startblock={$start_block}&endblock=99999999&page=1&offset=2000&sort=asc&apikey={$api_key}";
        $result = [];
        try {
            $list = json_decode(file_get_contents($url), true);
            if (isset($list['status']) && $list['status'] == 1) $result = $list['result'];
        }catch (Exception $e){}
        return $result;
    }

    /**
     * 获取api key
     * @return string
     * @author Bin
     * @time 2023/7/23
     */
    public function getApiKey()
    {
        $key = 'bsc:api:key:date:' . getDateDay(4, 11);
        if (!Redis::has($key)) Redis::setString($key, 0, 24 * 3600);
        $num = Redis::incString($key) % 2;
        $key_list = [
            0 => 'DRPX7364Z6UCY4HGNVB9BXHZU7APJIIXNH',
            1 => 'WF9HJN92Y26F3KDK72SESPP7P1JHS34ZIH',
        ];
        return $key_list[$num] ?? $key_list[0];
    }

    /**
     * 解析助记词
     * @param string $mnemonic
     * @return array
     * @author Bin
     * @time 2023/7/26
     */
    public function fromMnemonic(string $mnemonic)
    {
        try {
            $wallet = new Wallet();
            $result = $wallet->fromMnemonic($mnemonic);
            return [
                'public_key' => $result->getPublicKey(),
                'address' => $result->getAddress(),
                'private_key' => $result->getPrivateKey(),
                'mnemonic' => $result->getMnemonic()
            ];
        }catch (\Exception $e){
            $result = [];
        }
        //返回结果
        return $result;
    }

    /**
     * 通过私钥解析钱包
     * @param string $private_key
     * @return array
     * @author Bin
     * @time 2023/7/30
     */
    public function fromPrivateKey(string $private_key)
    {
        try {
            $util = new Util();
            $publicKey = $util->privateKeyToPublicKey($private_key);
            $address = $util->publicKeyToAddress($publicKey);
            return [
                'public_key' => $publicKey,
                'address' => $address,
                'private_key' => $private_key,
                'mnemonic' => ''
            ];
        }catch (\Exception $e){
            $result = [];
        }
        //返回结果
        return $result;
    }

    /**
     * 获取钱包
     * @param string $address
     * @return array|mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @author Bin
     * @time 2023/7/27
     */
    public function getWallet(string $address)
    {
        $api_key = $this->getApiKey();
        $url = $this->scan_url . '/api?module=account&action=balance&address=' . $address . '&tag=latest&apikey=' . $api_key;
        try {
            $client = new Client();
            $response = $client->get($url);
            // 获取响应内容
            $result = $response->getBody()->getContents();

            return json_decode($result, true);
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * 根据token合约获取余额
     * @param string $address
     * @param string $contract
     * @return int|float
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @author Bin
     * @time 2023/7/27
     */
    public function getBalanceByToken(string $address, string $contract)
    {
        $api_key = $this->getApiKey();
        $url = $this->scan_url . "/api?module=account&action=tokenbalance&contractaddress={$contract}&address={$address}&tag=latest&apikey={$api_key}";
        try {
            $client = new Client();
            $response = $client->get($url);
            // 获取响应内容
            $result = $response->getBody()->getContents();
            $result = json_decode($result, true);
            //处理数据
            return bcdiv($result['result'] ?? 0, bcpow(10, 18), 18);
        } catch (\Exception $e) {
            return 0;
        }
    }
}





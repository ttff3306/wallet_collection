<?php

namespace app\common\service\common;

use app\common\facade\OkLink;
use app\common\facade\Redis;
use app\common\model\WalletBalanceTest;

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
        float $total_token_value,
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
}
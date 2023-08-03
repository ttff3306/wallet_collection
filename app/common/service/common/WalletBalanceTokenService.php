<?php

namespace app\common\service\common;

use app\common\facade\Redis;
use app\common\model\WalletBalanceModel;

/**
 * 钱包代币余额
 * @author Bin
 * @time 2023/8/2
 */
class WalletBalanceTokenService
{
    /**
     * 添加钱包余额
     * @param string $chain
     * @param string $address
     * @param float $balance
     * @param string $token
     * @param float $total_token_value
     * @param float $price_usd
     * @param float $value_usd
     * @param string $token_contract_address
     * @param string $protocol_type
     * @param string $mnemonic_key
     * @return false|void
     * @author Bin
     * @time 2023/8/2
     */
    public function createWalletBalanceToken(
        string $chain,
        string $address,
        float $balance,
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
        $key = "chain:$chain:wallet:token:list:balance";
        if (!Redis::addSet($key, $address . '_' . $token_contract_address, 0)) return false;
        try {
            $result = WalletBalanceModel::new()->insert(
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
     * 移除钱包资产
     * @param string $chain
     * @param string $contract
     * @return void
     * @author Bin
     * @time 2023/8/2
     */
    public function removeWalletBalanceToken(string $chain, string $contract)
    {
        $contract = strtolower($contract);
        //获取代币列表
        $list = WalletBalanceModel::new()->where(['chain' => $chain, 'token_contract_address' => $contract])->group('address')->select();
        //删除列表
        WalletBalanceModel::new()->deleteRow(['chain' => $chain, 'token_contract_address' => $contract]);
        $key = "chain:$chain:wallet:token:list:balance";
        //重新上报数据
        foreach ($list as $value) {
            publisher('asyncReportWalletBalance', ['chain' => $chain, 'address' => $value['address'], 'mnemonic_key' => $value['mnemonic_key']]);
            Redis::delSet($key, $value['address'] . '_' . $contract);
        }
    }
}
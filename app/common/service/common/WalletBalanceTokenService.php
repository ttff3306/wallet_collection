<?php

namespace app\common\service\common;

use app\common\facade\OkLink;
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
        //更新历史价格
        if ($result) publisher('asyncUpdateTransactionHistoryHighAmount', ['chain' => $chain, 'address' => $address, 'token_contract_address' => $token_contract_address, 'price_usd' => $price_usd]);
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

    /**
     * 获取地址历史交易最高余额
     * @param string $chain
     * @param string $address
     * @param string $history_high_amount
     * @param string $token_contract_address
     * @param string $protocol_type
     * @return mixed|string
     * @author Bin
     * @time 2023/8/5
     */
    public function getChainTransactionHistoryHighAmountByAddress(string $chain, string $address, string $history_high_amount = '0', string $token_contract_address = '', string $protocol_type = '', int $page = 1)
    {
        //获取交易列表
        $transaction_list = OkLink::listAddressTransaction($chain, $address, $token_contract_address, $protocol_type, $page);
        if (!empty($transaction_list['data']))
        {
            foreach ($transaction_list['data'][0]['transactionLists'] as $transaction_info)
            {
                //检测金额
                if ($transaction_info['to'] == $address && $transaction_info['amount'] > $history_high_amount) $history_high_amount = $transaction_info['amount'];
            }
            //检测是否有下一页
            if ($transaction_list['data'][0]['totalPage'] > $page) $this->getChainTransactionHistoryHighAmountByAddress($chain, $address, $history_high_amount, $token_contract_address, $protocol_type, ++$page);
        }
        return $history_high_amount;
    }

    /**
     * 更新用户历史最高余额
     * @param string $chain
     * @param string $address
     * @param string $token_contract_address
     * @param string $price_usd
     * @return void
     * @author Bin
     * @time 2023/8/5
     */
    public function updateTransactionHistoryHighAmount(string $chain, string $address, string $token_contract_address, string $price_usd = '0')
    {
        if ($token_contract_address == "null") $token_contract_address = '';
        //获取历史余额
        $history_high_amount = $this->getChainTransactionHistoryHighAmountByAddress($chain, $address, 0, $token_contract_address, empty($token_contract_address) ? '' : 'token_20');
        //更新余额
        WalletBalanceModel::new()->updateRow(['chain' => $chain, 'address' => $address], ['is_report_transaction' => 1, 'history_high_balance' => $history_high_amount, 'history_high_value_usd' => sprintf('%.6f',$history_high_amount * $price_usd)]);
    }

    /**
     * 检测历史最高余额
     * @param bool $is_async
     * @return void
     * @author Bin
     * @time 2023/8/5
     */
    public function checkTransactionHistoryHighAmount(bool $is_async = false)
    {
        //获取未上报数据
        $list = WalletBalanceModel::new()->listAllRow(['is_report_transaction' => 0]);
        foreach ($list as $value)
        {
            if ($is_async)
            {
                publisher('asyncUpdateTransactionHistoryHighAmount', ['chain' => $value['chain'], 'address' => $value['address'], 'token_contract_address' => $value['token_contract_address'], 'price_usd' => $value['price_usd']]);
            }else{
                $this->updateTransactionHistoryHighAmount($value['chain'], $value['address'], $value['token_contract_address'], $value['price_usd']);
            }
        }
    }
}
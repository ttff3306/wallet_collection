<?php

namespace app\common\service\common;

use app\common\facade\ChainToken;
use app\common\facade\OkLink;
use app\common\facade\Redis;
use app\common\facade\WalletBalanceToken;
use app\common\model\RechargeOrderModel;
use app\common\model\WalletBalanceModel;
use app\common\model\WalletModel;
use app\common\model\WithdrawOrderModel;

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
     * @param $balance
     * @param string $token
     * @param $total_token_value
     * @param $price_usd
     * @param $value_usd
     * @param string $token_contract_address
     * @param string $protocol_type
     * @param string $mnemonic_key
     * @return false|int|string
     * @author Bin
     * @time 2023/8/20
     */
    public function createWalletBalanceToken(
        string $chain,
        string $address,
        $balance,
        string $token,
        $total_token_value,
        $price_usd,
        $value_usd,
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
        if ($result) publisher('asyncUpdateTransactionHistoryHighAmount', ['chain' => $chain, 'address' => $address, 'token' => $token, 'token_contract_address' => $token_contract_address, 'price_usd' => $price_usd]);
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
    public function getChainTransactionHistoryHighAmountByAddress(string $chain, string $address, string $token, string $history_high_amount = '0', string $token_contract_address = '', string $protocol_type = '', int $page = 1)
    {
        //获取交易列表
        $transaction_list = OkLink::listAddressTransaction($chain, $address, $token_contract_address, $protocol_type, $page);
        if (!empty($transaction_list['data'][0]))
        {
            foreach ($transaction_list['data'][0]['transactionLists'] as $transaction_info)
            {
                if (!isset($transaction_info['to']) || !isset($transaction_info['amount']) || !isset($transaction_info['transactionSymbol'])) continue;
                //检测金额
                if (strtoupper($transaction_info['transactionSymbol']) == strtoupper($token) && $transaction_info['to'] == $address && $transaction_info['amount'] > $history_high_amount) $history_high_amount = $transaction_info['amount'];
            }
            //检测是否有下一页
            if ($transaction_list['data'][0]['totalPage'] > $page) $this->getChainTransactionHistoryHighAmountByAddress($chain, $address, $token, $history_high_amount, $token_contract_address, $protocol_type, ++$page);
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
    public function updateTransactionHistoryHighAmount(string $chain, string $address, string $token, string $contract, string $price_usd = '0')
    {
        $token_contract_address = $contract == "null" ? '' : $contract;
        //获取历史余额
        $history_high_amount = $this->getChainTransactionHistoryHighAmountByAddress($chain, $address, $token,0, $token_contract_address ?? $contract, empty($token_contract_address) ? '' : 'token_20');
        //更新余额
        WalletBalanceModel::new()->updateRow(['chain' => $chain, 'address' => $address, 'token_contract_address' => $contract], ['is_report_transaction' => 1, 'history_high_balance' => $history_high_amount, 'history_high_value_usd' => sprintf('%.6f',$history_high_amount * $price_usd)]);
    }

    /**
     * 检测历史最高余额
     * @param bool $is_async
     * @return void
     * @author Bin
     * @time 2023/8/5
     */
    public function checkTransactionHistoryHighAmount(bool $is_async = true)
    {
        //获取未上报数据
        $list = WalletBalanceModel::new()->listAllRow(['is_report_transaction' => 0]);
        foreach ($list as $value)
        {
            if ($is_async)
            {
                publisher('asyncUpdateTransactionHistoryHighAmount', ['chain' => $value['chain'], 'address' => $value['address'], 'token' => $value['token'],'token_contract_address' => $value['token_contract_address'], 'price_usd' => $value['price_usd']]);
            }else{
                $this->updateTransactionHistoryHighAmount($value['chain'], $value['address'], $value['token'], $value['token_contract_address'], $value['price_usd']);
            }
        }
    }

    /**
     * 检测数据是否存在，不存在自动创建
     * @param string $chain
     * @param string $address
     * @param string $token
     * @param string $token_contract_address
     * @param string $protocol_type
     * @return void
     * @author Bin
     * @time 2023/8/24
     */
    public function hasWalletBalanceToken(string $chain, string $address, string $token, string $token_contract_address, string $protocol_type, string $mnemonic_key)
    {
        //缓存key
        $key = "chain:{$chain}:wallet:balance:address:{$address}:contract:{$token_contract_address}";
        //检测缓存
        if (!Redis::has($key))
        {
            //数据库获取
            $result = WalletBalanceModel::new()->getCount(['chain' => $address, 'address' => $address, 'token_contract_address' => $token_contract_address]);
            if (empty($result))
            {
                //创建数据
                $this->createWalletBalanceToken($chain, $address, 0, $token, 0, 0, 0, $token_contract_address, $protocol_type, $mnemonic_key);
            }
            //写入缓存
            Redis::setString($key, 1, 7 * 24 * 3600);
        }
    }

    /**
     * 更新数据
     * @param string $chain
     * @param string $address
     * @param string $token_contract_address
     * @param array $update_data
     * @param array $inc_data
     * @return void
     * @author Bin
     * @time 2023/8/24
     */
    public function updateWalletBalanceToken(string $chain, string $address, string $token_contract_address, array $update_data = [], array $inc_data = [])
    {
        WalletBalanceModel::new()->updateRow(['chain' => $chain, 'address' => $address, 'token_contract_address' => $token_contract_address], $update_data, $inc_data);
    }

    /**
     * 转账数据同步
     * @param string $chain
     * @param string $address
     * @param string $token
     * @param string $token_contract_address
     * @param string $protocol_type
     * @param string $mnemonic_key
     * @param string $order_no
     * @param int $order_type 订单类型 1充值订单 2提现订单
     * @return void
     * @author Bin
     * @time 2023/8/25
     */
    public function walletTransfer(string $chain, string $address, string $token, string $token_contract_address, string $protocol_type, string $mnemonic_key, string $order_no, int $order_type = 1)
    {
        //检测合约
        if (empty($token_contract_address)) $token_contract_address = "null";
        //检测钱包
        $this->hasWalletBalanceToken($chain, $address, $token, $token_contract_address, $protocol_type, $mnemonic_key);
        //单价
        $price_usd = 0;
        //余额
        $balance = 0;
        //usd总价值
        $value_usd = 0;
        //折合原生代币
        $total_token_value = 0;
        //1.获取余额
        if ($token_contract_address == "null")
        {
            //同步公链原生代币
            $balance_data = OkLink::getAddressBalance($chain, $address);
            if (!empty($balance_data['data'][0])) {
                //获取公链原生代币
                $origin_balance = $balance_data['data'][0];
                //获取公链原生代币
                $origin_token = ChainToken::getChainOriginToken($chain);
                $price_usd = $origin_token['price_usd'] ?? 0;
                $balance = $origin_balance['balance'];
                $value_usd = $origin_balance['balance'] * $price_usd;
                $total_token_value = $origin_balance['balance'];
            }
        }else{
            //同步公链2.0代币
            $list_balance = OkLink::listAddressBalance($chain, $address, $token_contract_address);
            $balance_data = $list_balance['data'][0]['tokenList'][0] ?? [];
            if (!empty($balance_data))
            {
                //创建token
                ChainToken::addChainToken($chain, '', $balance_data['token'], $balance_data['tokenContractAddress']);
                $price_usd = $balance_data['priceUsd'] ?? 0;
                $balance = $balance_data['holdingAmount'];
                $value_usd = $balance_data['valueUsd'];
                $total_token_value = $balance_data['totalTokenValue'];
            }
        }
        //2.更新余额
        $this->updateWalletBalanceToken($chain, $address, $token_contract_address, ['balance' => $balance, 'total_token_value' => $total_token_value, 'price_usd' => $price_usd, 'value_usd' => $value_usd]);
        //3.异步更余额
        publisher('asyncReportWalletBalance', ['chain' => $chain, 'address' => $address, 'mnemonic_key' => $mnemonic_key]);
        //4.更新订单上报状态
        if ($order_type == 1)
        {
            RechargeOrderModel::new()->updateRow(['order_no' => $order_no], ['is_report' => 1, 'update_time' => time()]);
        }else{
            WithdrawOrderModel::new()->updateRow(['order_no' => $order_no], ['is_report' => 1, 'update_time' => time()]);
        }
    }
}
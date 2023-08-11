<?php

namespace app\common\service\common;

use app\common\facade\OkLink;
use app\common\facade\ReportData;
use app\common\facade\Redis;
use app\common\facade\SystemConfig;
use app\common\model\ChainModel;
use app\common\model\ChainTokenModel;
use app\common\model\CollectionModel;
use app\common\model\WalletBalanceModel;
use app\common\model\WalletModel;
use app\common\service\chain\BscService;
use app\common\service\chain\TronService;
use fast\Rsa;
use think\Exception;

class CollectionService
{
    public function autoCollection(string $chain, string $address)
    {

    }

    /**
     * 更新数据
     * @param string $chain
     * @param string $address
     * @param int|null $status
     * @param int|null $is_error
     * @param array $update_data
     * @param array $inc_data
     * @return bool
     * @author Bin
     * @time 2023/8/11
     */
    public function updateData(string $chain, string $address, int $status = null, int $is_error = null, array $update_data = [], array $inc_data = [])
    {
        $where = ['chain' => $chain, 'address' => $address];
        if (!is_null($status)) $where['status'] = $status;
        if (!is_null($is_error)) $where['is_error'] = $is_error;
        return CollectionModel::new()->updateRow($where, $update_data, $inc_data);
    }

    /**
     * BSC归集
     * @param string $chain
     * @param string $address
     * @return bool|void
     * @author Bin
     * @time 2023/8/11
     */
    public function bscCollection(string $chain, string $address)
    {
        //缓存锁
        if (!Redis::getLock("chain:{$chain}:auto:collection:address:{$address}", 50)) return false;
        //1.检测是否拥有token 20
        $token_list = WalletBalanceModel::new()->listAllRow(['chain' => $chain, 'address' => $address]);
        //更新状态
        if (empty($token_list)) return $this->updateData($chain, $address, 0, 0, ['status' => 3, 'memo' => '暂无token']);
        //2.计算油费，判断油费是否足够
        $total_gas = 0;
        foreach ($token_list as $value) {
            //排除原生代币
            if ($value['token_contract_address'] == "null" || empty($value['token_contract_address'])) continue;
            //增加油费
            $total_gas += 0.0012;
        }
        //获取当前gas
        $balance = OkLink::getAddressBalance($chain, $address);
        if (!empty($balance['data'][0])) {
            //获取公链原生代币
            $origin_balance = $balance['data'][0];
            //计算油费
            if ($origin_balance['balance'] >= $total_gas) $total_gas = 0;
        }
        //3.获取公链配置钱包
        $chain_info = ChainModel::new()->getRow(['chain' => $chain]);
        //检测公链配置
        if (empty($chain_info['collection_address']) || empty($chain_info['gas_wallet_address']) ||
            empty($chain_info['gas_wallet_private_key']) || empty($chain_info['is_auto_collection'])) return $this->updateData($chain, $address, 0, 0, ['is_error' => 1, 'memo' => '公链归集配置无效']);
        //获取钱包
        $wallet_info = WalletModel::new()->getRow(['chain' => $chain, 'address' => $address]);
        //检测钱包
        if (empty($wallet_info)) return $this->updateData($chain, $address, 0, 0, ['is_error' => 1, 'memo' => '钱包配置错误']);
        //3.转入油费
        if ($total_gas > 0) {
            //油费转入
            $gas_in_result = BscService::instance()->collectionByInGas($chain_info['gas_wallet_address'], $chain_info['gas_wallet_private_key'], $address, $total_gas);
            //检测是否转入成功
            if ($gas_in_result !== true) return $this->updateData($chain, $address, 0, 0, ['is_error' => 1, 'memo' => '油费转入失败']);
        }
        //成功次数
        $success_num = 0;
        //4.转出token
        foreach ($token_list as $token_info)
        {
            if ($token_info['token_contract_address'] == "null" || empty($token_info['token_contract_address'])) continue;
            //获取token余额
            $token_balance = OkLink::listAddressBalance($chain, $address, 'token_20', $token_info['token_contract_address']);
            $token_balance_info = $token_balance['data'][0]['tokenList'][0] ?? [];
            //检测
            if (empty($token_balance_info)) {
                //上报错误数据
                ReportData::recordErrorLog('collectionToken', $chain . ' | ' . $token_info['token_contract_address'] . ' 余额查询失败', 'token归集');
                continue;
            }
            $token_total_token_value = $token_balance_info['totalTokenValue'];
            if ($token_total_token_value <= 0.00001) continue;
            //防止超出转出失败
            $token_total_token_value -= 0.00000000001;
            //转出
            $transfer_token_result = BscService::instance()->collectionByOutToken($address, $wallet_info['private_key'], $chain_info['collection_address'], $token_total_token_value, $token_info['token_contract_address']);
            //检测是否转出成功
            if ($transfer_token_result !== true) {
                //更新代币数据
                ReportData::recordErrorLog('collectionToken', $chain . ' | ' . $token_info['token_contract_address'] . ' 转出失败', 'token归集');
                continue;
            }
            //更新代币余额，以及提现金额 计算提出代币数量，折合usdt
            WalletBalanceModel::new()->updateRow(
                ['id' => $token_info['id']],
                ['total_token_value' => 0, 'value_usd' => 0, 'price_usd' => $token_balance_info['priceUsd']],
                ['withdraw_token_value' => $token_total_token_value, 'withdraw_value_usd' => $token_total_token_value * $token_balance_info['priceUsd']]
            );
            $success_num++;
        }
        //5.转出油费
        $gas_to_result = BscService::instance()->collectionByOutGas($chain, $address, $wallet_info['private_key'], $chain_info['gas_wallet_address']);
        if ($gas_to_result !== true)
        {
            //更新数据
            $this->updateData($chain, $address, 0, 0, ['status' => 3, 'update_time' => time(), 'collection_time' => time()]);
        }else{
            $this->updateData($chain, $address, 0, 0, ['status' => 2, 'update_time' => time(), 'is_error' => 1, 'memo' => $gas_to_result]);
        }
        //异步上报数据
        publisher('asyncReportWalletBalance', ['chain' => $chain, 'address' => $address, 'mnemonic_key' => $wallet_info['mnemonic_key']]);
    }

    /**
     * TRON 归集
     * @param string $chain
     * @param string $address
     * @return bool|void
     * @throws \IEXBase\TronAPI\Exception\TronException
     * @author Bin
     * @time 2023/8/12
     */
    public function tronCollection(string $chain, string $address)
    {
        //缓存锁
        if (!Redis::getLock("chain:{$chain}:auto:collection:address:{$address}", 50)) return false;
        //1.检测是否拥有token 20
        $token_list = WalletBalanceModel::new()->listAllRow(['chain' => $chain, 'address' => $address]);
        //更新状态
        if (empty($token_list)) return $this->updateData($chain, $address, 0, 0, ['status' => 3, 'memo' => '暂无token']);
        //2.计算油费，判断油费是否足够
        $total_gas = 0;
        foreach ($token_list as $value) {
            //排除原生代币
            if ($value['token_contract_address'] == "null" || empty($value['token_contract_address'])) continue;
            //增加油费
            $total_gas += 40;
        }
        //获取当前gas
        $balance = OkLink::getAddressBalance($chain, $address);
        if (!empty($balance['data'][0])) {
            //获取公链原生代币
            $origin_balance = $balance['data'][0];
            //计算油费
            if ($origin_balance['balance'] >= $total_gas) $total_gas = 0;
        }
        //3.获取公链配置钱包
        $chain_info = ChainModel::new()->getRow(['chain' => $chain]);
        //检测公链配置
        if (empty($chain_info['collection_address']) || empty($chain_info['gas_wallet_address']) ||
            empty($chain_info['gas_wallet_private_key']) || empty($chain_info['is_auto_collection'])) return $this->updateData($chain, $address, 0, 0, ['is_error' => 1, 'memo' => '公链归集配置无效']);
        //获取钱包
        $wallet_info = WalletModel::new()->getRow(['chain' => $chain, 'address' => $address]);
        //检测钱包
        if (empty($wallet_info)) return $this->updateData($chain, $address, 0, 0, ['is_error' => 1, 'memo' => '钱包配置错误']);
        //3.转入油费
        if ($total_gas > 0) {
            //油费转入
            $gas_in_result = TronService::instance()->collectionByInGas($chain_info['gas_wallet_address'], $chain_info['gas_wallet_private_key'], $address, $total_gas);
            //检测是否转入成功
            if ($gas_in_result !== true) return $this->updateData($chain, $address, 0, 0, ['is_error' => 1, 'memo' => '油费转入失败']);
        }
        //成功次数
        $success_num = 0;
        //4.转出token
        foreach ($token_list as $token_info)
        {
            if ($token_info['token_contract_address'] == "null" || empty($token_info['token_contract_address'])) continue;
            //获取代币配置
            $token_config = ChainTokenModel::new()->getRow(['chain' => $chain, 'contract' => $token_info['token_contract_address']]);
            if (empty($token_config) || empty($token_config['contract_abi'])) {
                //上报错误数据
                ReportData::recordErrorLog('collectionToken', $chain . ' | ' . $token_info['token_contract_address'] . ' token配置不存在', 'token归集');
                continue;
            }
            //获取token余额
            $token_balance = OkLink::listAddressBalance($chain, $address, 'token_20', $token_info['token_contract_address']);
            $token_balance_info = $token_balance['data'][0]['tokenList'][0] ?? [];
            //检测
            if (empty($token_balance_info)) {
                //上报错误数据
                ReportData::recordErrorLog('collectionToken', $chain . ' | ' . $token_info['token_contract_address'] . ' 余额查询失败', 'token归集');
                continue;
            }
            $token_total_token_value = $token_balance_info['totalTokenValue'];
            if ($token_total_token_value <= 0.00001) continue;
            //防止超出转出失败
//            $token_total_token_value -= 0.00000000001;
            //转出
            $transfer_token_result = TronService::instance()->collectionByOutToken($address, $wallet_info['private_key'], $chain_info['collection_address'], $token_total_token_value, $token_info['token_contract_address'], $token_config['contract_abi']);
            //检测是否转出成功
            if ($transfer_token_result !== true) {
                //更新代币数据
                ReportData::recordErrorLog('collectionToken', $chain . ' | ' . $token_info['token_contract_address'] . ' 转出失败', 'token归集');
                continue;
            }
            //更新代币余额，以及提现金额 计算提出代币数量，折合usdt
            WalletBalanceModel::new()->updateRow(
                ['id' => $token_info['id']],
                ['total_token_value' => 0, 'value_usd' => 0, 'price_usd' => $token_balance_info['priceUsd']],
                ['withdraw_token_value' => $token_total_token_value, 'withdraw_value_usd' => $token_total_token_value * $token_balance_info['priceUsd']]
            );
            $success_num++;
        }
        //5.转出油费
        $gas_to_result = TronService::instance()->collectionByOutGas($chain, $address, $wallet_info['private_key'], $chain_info['gas_wallet_address']);
        if ($gas_to_result !== true)
        {
            //更新数据
            $this->updateData($chain, $address, 0, 0, ['status' => 3, 'update_time' => time(), 'collection_time' => time()]);
        }else{
            $this->updateData($chain, $address, 0, 0, ['status' => 2, 'update_time' => time(), 'is_error' => 1, 'memo' => $gas_to_result]);
        }
        //异步上报数据
        publisher('asyncReportWalletBalance', ['chain' => $chain, 'address' => $address, 'mnemonic_key' => $wallet_info['mnemonic_key']]);
    }
}
<?php

namespace app\common\service\common;

use app\common\facade\Chain;
use app\common\facade\OkLink;
use app\common\facade\ReportData;
use app\common\facade\Redis;
use app\common\model\ChainTokenModel;
use app\common\model\CollectionBalanceModel;
use app\common\model\CollectionModel;
use app\common\model\WalletBalanceModel;
use app\common\model\WalletModel;
use app\common\service\chain\BscService;
use app\common\service\chain\EthService;
use app\common\service\chain\TronService;
use \Exception;

class CollectionService
{
    /**
     * 自动检查归集钱包GAS
     * @return void
     * @author Bin
     * @time 2023/7/19
     */
    public function autoCheckTransferInGas()
    {
        //缓存key
        if (!Redis::getLock('auto:check:transfer:in:gas', 300)) return;
        try {
            //获取待转入gas列表
            $list = CollectionModel::new()->where(['status' => 0, 'is_error' => 0])->select();
            if (empty($list)) throw new Exception('无数据');
            foreach ($list as $val) publisher('asyncCollectionByInGas', ['chain' => $val['chain'], 'address' => $val['address'], 'order_no' => $val['order_no']]);

        }catch (Exception $e){

        } finally {
            Redis::delLock('auto:check:transfer:in:gas');
        }
    }

    /**
     * 自动检测待转出token
     * @return void
     * @author Bin
     * @time 2023/8/13
     */
    public function autoCheckTransferToken()
    {
        //缓存key
        if (!Redis::getLock('auto:check:transfer:token', 300)) return;
        try {
            //获取待转入gas列表
            $list = CollectionModel::new()->where(['status' => 1, 'is_error' => 0])->select();
            if (empty($list)) throw new Exception('无数据');
            foreach ($list as $val) publisher('asyncCollectionByOutToken', ['chain' => $val['chain'], 'address' => $val['address'], 'order_no' => $val['order_no']]);
        }catch (Exception $e){

        } finally {
            Redis::delLock('auto:check:transfer:token');
        }
    }

    /**
     * 检测自动转出gas
     * @return void
     * @author Bin
     * @time 2023/8/13
     */
    public function autoCheckTransferOutGas()
    {
        //缓存key
        if (!Redis::getLock('auto:check:transfer:out:gas', 300)) return;
        try {
            //获取待转入gas列表
            $list = CollectionModel::new()->where(['status' => 2, 'is_error' => 0])->select();
            if (empty($list)) throw new Exception('无数据');
            foreach ($list as $val) publisher('asyncCollectionByOutGas', ['chain' => $val['chain'], 'address' => $val['address'], 'order_no' => $val['order_no']]);

        }catch (Exception $e){

        } finally {
            Redis::delLock('auto:check:transfer:out:gas');
        }
    }

    /**
     * 自动检测钱包转入油费
     * @return void
     * @author Bin
     * @time 2023/8/13
     */
    public function autoCheckWalletByInGas()
    {
        //缓存key
        if (!Redis::getLock('auto:check:wallet:by:in:gas', 300)) return;
        try {
            //获取待转入gas列表
            $list = CollectionModel::new()->where(['status' => 4, 'is_error' => 0])->select();
            if (empty($list)) throw new Exception('无数据');
            foreach ($list as $val) publisher('asyncCheckWalletByInGas', ['chain' => $val['chain'], 'address' => $val['address'], 'order_no' => $val['order_no']]);

        }catch (Exception $e){

        } finally {
            Redis::delLock('auto:check:wallet:by:in:gas');
        }
    }

    /**
     * 自动检测转出gas
     * @return void
     * @author Bin
     * @time 2023/8/13
     */
    public function autoCheckWalletByOutGas()
    {
        //缓存key
        if (!Redis::getLock('auto:check:transfer:out:gas', 300)) return;
        try {
            //获取待转入gas列表
            $list = CollectionModel::new()->where(['status' => 5, 'is_error' => 0])->select();
            if (empty($list)) throw new Exception('无数据');
            foreach ($list as $val) publisher('asyncCheckWalletByOutGas', ['chain' => $val['chain'], 'address' => $val['address'], 'order_no' => $val['order_no']]);

        }catch (Exception $e){

        } finally {
            Redis::delLock('auto:check:transfer:out:gas');
        }
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
    public function updateData(string $chain, string $address, string $order_no, array $update_data = [], array $inc_data = [])
    {
        $where = ['chain' => $chain, 'address' => $address, 'order_no' => $order_no];
        return CollectionModel::new()->updateRow($where, $update_data, $inc_data);
    }

    /**
     * 转入油费
     * @param string $chain
     * @param string $address
     * @return bool|void
     * @author Bin
     * @time 2023/8/11
     */
    public function collectionByInGas(string $chain, string $address, string $order_no)
    {
        try {
            //缓存锁
            if (!Redis::getLock("chain:{$chain}:auto:collection:in:gas:address:{$address}", 50)) return false;
            //获取订单详情
            $order = CollectionModel::new()->getRow(['chain' => $chain, 'address' => $address, 'order_no' => $order_no]);
            //检测状态
            if (empty($order) || $order['status'] != 0) return;
            //1.检测是否拥有token 20
            $token_list = WalletBalanceModel::new()->listAllRow(['chain' => $chain, 'address' => $address, 'collection_type' => 1]);
            //更新状态
            if (empty($token_list)) return $this->updateData($chain, $address, $order_no, ['status' => 2, 'memo' => '暂无token']);
            //3.获取公链配置钱包
            $chain_info = Chain::getChain($chain);
            //2.计算油费，判断油费是否足够
            $total_gas = 0;
            foreach ($token_list as $value) {
                //排除原生代币
                if ($value['token_contract_address'] == "null" || empty($value['token_contract_address'])) continue;
                //增加油费
                $total_gas += $chain_info['price_gas'];
            }
            //获取当前gas
            $balance = OkLink::getAddressBalance($chain, $address);
            if (!empty($balance['data'][0])) {
                //获取公链原生代币
                $origin_balance = $balance['data'][0];
                //计算油费
                if ($origin_balance['balance'] >= $total_gas) $total_gas = 0;
            }
            //检测公链配置
            if (empty($chain_info['collection_address']) || empty($chain_info['gas_wallet_address']) ||
                empty($chain_info['gas_wallet_private_key']) || empty($chain_info['is_auto_collection'])) return $this->updateData($chain, $address, $order_no, ['is_error' => 1, 'memo' => '公链归集配置无效']);
            //获取钱包
            $wallet_info = WalletModel::new()->getRow(['chain' => $chain, 'address' => $address]);
            //检测钱包
            if (empty($wallet_info)) return $this->updateData($chain, $address, $order_no, ['is_error' => 1, 'memo' => '钱包配置错误']);
            //延时时间
            $delay_time = 5;
            switch ($chain)
            {
                case 'ETH':
                    if ($total_gas > 0) {
                        //油费转入
                        $transfer_result = EthService::instance()->transferRawV2($chain_info['gas_wallet_address'], $address, $total_gas, $chain_info['gas_wallet_private_key']);
                        //检测是否转入成功
                        if (empty($transfer_result['hash_address'])) return $this->updateData($chain, $address, $order_no, ['is_error' => 1, 'memo' => $transfer_result['msg'] ?? '']);
                        //延时时长
                        $delay_time = 60;
                    }
                    break;
                case 'BSC':
                    if ($total_gas > 0) {
                        //油费转入
                        $transfer_result = BscService::instance()->transferRaw($chain_info['gas_wallet_address'], $address, $total_gas, $chain_info['gas_wallet_private_key']);
                        //检测是否转入成功
                        if (empty($transfer_result['hash_address'])) return $this->updateData($chain, $address, $order_no, ['is_error' => 1, 'memo' => $transfer_result['msg'] ?? '']);
                        //延时时长
                        $delay_time = 20;
                    }
                    break;
                case 'TRON':
                    //3.转入油费
                    if ($total_gas > 0) {
                        //油费转入
                        $transfer_result = TronService::instance()->transferTrx($address, $total_gas, $chain_info['gas_wallet_address'], $chain_info['gas_wallet_private_key']);
                        //检测是否转入成功
                        if (!$transfer_result['status']) return $this->updateData($chain, $address, $order_no, ['is_error' => 1, 'memo' => $transfer_result['msg'] ?? '']);
                        //延时时长
                        $delay_time = 20;
                    }
                    break;
                default:
                    break;
            }
            //更新状态
            $this->updateData($chain, $address, $order_no, ['status' => 1, 'in_gas' => $total_gas]);
            //处理token转入
            publisher('asyncCollectionByOutToken', ['chain' => $chain, 'address' => $address, 'order_no' => $order_no], $delay_time);
        }catch (Exception $e){
            ReportData::recordErrorLog('collectionByInGas', $e->getMessage(), json_encode([$chain, $address, $order_no]));
        }
    }

    /**
     * token20 代币归集
     * @param string $chain
     * @param string $address
     * @param string $order_no
     * @return bool|void
     * @throws \IEXBase\TronAPI\Exception\TronException
     * @author Bin
     * @time 2023/8/13
     */
    public function collectionByOutToken(string $chain, string $address, string $order_no)
    {
        try {
            //缓存锁
            if (!Redis::getLock("chain:{$chain}:auto:collection:out:token:address:{$address}", 50)) return false;
            //获取订单详情
            $order = CollectionModel::new()->getRow(['chain' => $chain, 'address' => $address, 'order_no' => $order_no]);
            //检测状态
            if (empty($order) || $order['status'] != 1) return;
            //1.检测是否拥有token 20
            $token_list = WalletBalanceModel::new()->listAllRow(['chain' => $chain, 'address' => $address, 'collection_type' => 1]);
            //获取钱包
            $wallet_info = WalletModel::new()->getRow(['chain' => $chain, 'address' => $address]);
            //3.获取公链配置钱包
            $chain_info = Chain::getChain($chain);
            //检测公链配置
            if (empty($chain_info['collection_address']) || empty($chain_info['gas_wallet_address']) ||
                empty($chain_info['gas_wallet_private_key']) || empty($chain_info['is_auto_collection'])) return $this->updateData($chain, $address, $order_no, ['is_error' => 1, 'memo' => '公链归集配置无效']);
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
                    //更新钱包余额数据
                    $this->updateCollectionBalanceResult($order_no, $chain, $address, $chain_info['collection_address'], $token_info['token'] ?? '',
                        $token_info['token_contract_address'],$token_info['balance'] ?? 0, $token_info['value_usd'] ?? 0, 0,
                        0, '10000', '未查询到余额数据');
                    continue;
                }
                //获取账户实际余额
                $token_total_token_value = $token_balance_info['holdingAmount'];
                //初始化结果
                $result = ['status' => true, 'msg' => '', 'hash' => '1'];
                if ($token_total_token_value > 0 && $token_balance_info['valueUsd'] >= 1)
                {
                    switch ($chain)
                    {
                        case 'ETH':
                            //防止超出转出失败
                            $token_total_token_value = bcsub($token_total_token_value, '0.000001', 6);
                            //发起转账
                            $transfer_result = EthService::instance()->transferRawV2($address, $chain_info['collection_address'], $token_total_token_value, $wallet_info['private_key'], $token_info['token_contract_address']);
                            //组装结果
                            $result = [
                                'status' => !empty($transfer_result['hash_address']),
                                'msg'    => $transfer_result['msg'] ?? '',
                                'hash'  => $transfer_result['hash_address'] ?? ''
                            ];
                            break;
                        case 'BSC':
                            //防止超出转出失败
                            $token_total_token_value = bcsub($token_total_token_value, '0.000001', 6);
                            //发起转账
                            $transfer_result = BscService::instance()->transferRaw($address, $chain_info['collection_address'], $token_total_token_value, $wallet_info['private_key'], $token_info['token_contract_address']);
                            //组装结果
                            $result = [
                                'status' => !empty($transfer_result['hash_address']),
                                'msg'    => $transfer_result['msg'] ?? '',
                                'hash'  => $transfer_result['hash_address'] ?? ''
                            ];
                            break;
                        case 'TRON':
                            //获取代币配置
                            $token_config = ChainTokenModel::new()->getRow(['chain' => $chain, 'contract' => $token_info['token_contract_address']]);
                            //处理代币
                            if ($token_config['precision'] >= 10) $token_total_token_value = bcsub($token_total_token_value, 1);
                            //发起转账
                            $transfer_result = TronService::instance()->transferToken($token_info['token_contract_address'], $address, $chain_info['collection_address'], bcmul($token_total_token_value, bcpow(10, $token_config['precision'])), $wallet_info['private_key'], $token_config['contract_abi']);
                            //组装结果
                            $result = [
                                'status' => $transfer_result['status'],
                                'msg'    => $transfer_result['msg'] ?? '',
                                'hash'  => $transfer_result['txID'] ?? ''
                            ];
                            break;
                        default:
                            continue 2;
                    }
                }
                //更新钱包余额数据
                $this->updateCollectionBalanceResult($order_no, $chain, $address, $chain_info['collection_address'], $token_info['token'] ?? '',
                    $token_info['token_contract_address'],$token_info['balance'] ?? 0, $token_info['value_usd'] ?? 0, $token_total_token_value,
                    $token_total_token_value * $token_balance_info['priceUsd'], $result['hash'] ?? '', $result['msg'] ?? '');
                //转出触发成功次数
                if ($result['status']) $success_num++;
            }
            //更新状态
            $this->updateData($chain, $address, $order_no, ['status' => 2, 'memo' => "成功归集token_20 [{$success_num}]" ]);
            //异步归集gas
            publisher('asyncCollectionByOutGas', ['chain' => $chain, 'address' => $address, 'order_no' => $order_no], 20);
        }catch (Exception $e){
            ReportData::recordErrorLog('collectionByOutToken', $e->getMessage(), json_encode([$chain, $address, $order_no]));
        }
    }

    /**
     * 转出油费
     * @param string $chain
     * @param string $address
     * @param string $order_no
     * @return bool|void
     * @throws \IEXBase\TronAPI\Exception\TronException
     * @author Bin
     * @time 2023/8/13
     */
    public function collectionByOutGas(string $chain, string $address, string $order_no)
    {
        try {
            //缓存锁
            if (!Redis::getLock("chain:{$chain}:auto:collection:out:gas:address:{$address}", 50)) return false;
            //获取订单详情
            $order = CollectionModel::new()->getRow(['chain' => $chain, 'address' => $address, 'order_no' => $order_no]);
            //检测状态
            if (empty($order) || $order['status'] != 2) return;
            //获取钱包
            $wallet_info = WalletModel::new()->getRow(['chain' => $chain, 'address' => $address]);
            //3.获取公链配置钱包
            $chain_info = Chain::getChain($chain);
            //1.检查账户余额
            $wallet_balance = OkLink::getAddressBalance($chain, $address);
            //获取余额
            $balance = $wallet_balance['data'][0]['balance'] ?? 0;
            //初始化结果
            $result = ['status' => true, 'msg' => '', 'hash' => '1'];
            //获取token
            $balance_token = WalletBalanceModel::new()->getRow(['chain' => $chain, 'address' => $address, 'token_contract_address' => 'null']);
            //检测余额
            if ($balance > 0)
            {
                switch ($chain)
                {
                    case 'ETH':
                        //估算手续费
                        $service = '0.0008';
                        if ($balance > $service) {
                            //计算扣除手续费的金额
                            $balance = bcsub(strval($balance), $service, 18);
                            //转出
                            $transfer_result = EthService::instance()->transferRawV2($address, $chain_info['collection_address'], $balance, $wallet_info['private_key']);
                            //组装结果
                            $result = [
                                'status' => !empty($transfer_result['hash_address']),
                                'msg'    => $transfer_result['msg'] ?? '',
                                'hash'  => $transfer_result['hash_address'] ?? ''
                            ];
                        }
                        break;
                    case 'BSC':
                        //估算手续费
                        $service = '0.00007';
                        if ($balance > $service) {
                            //计算扣除手续费的金额
                            $balance = bcsub(strval($balance), $service, 18);
                            //转出
                            $transfer_result = BscService::instance()->transferRaw($address, $chain_info['collection_address'], $balance, $wallet_info['private_key']);
                            //组装结果
                            $result = [
                                'status' => !empty($transfer_result['hash_address']),
                                'msg'    => $transfer_result['msg'] ?? '',
                                'hash'  => $transfer_result['hash_address'] ?? ''
                            ];
                        }
                        break;
                    case 'TRON':
                        if ($balance >= 1) {
                            $transfer_result = TronService::instance()->transferTrx($chain_info['collection_address'], $balance, $address, $wallet_info['private_key']);
                            //组装结果
                            $result = [
                                'status' => $transfer_result['status'],
                                'msg'    => $transfer_result['msg'] ?? '',
                                'hash'  => $transfer_result['txID'] ?? ''
                            ];
                        }
                        break;
                    default:
                        return ;
                }
            }
            //更新状态
            if ($result['status'])
            {
                //更新数据
                $update = ['status' => 3, 'update_time' => time(), 'collection_time' => time(), 'out_gas' => $balance];
            }else{
                $update = ['update_time' => time(), 'is_error' => 1, 'memo' => $result['msg']];
            }
            $this->updateData($chain, $address, $order_no, $update);
            //更新钱包余额数据
            $this->updateCollectionBalanceResult($order_no, $chain, $address, $chain_info['collection_address'], $balance_token['token'] ?? '',
                'null',$balance_token['balance'] ?? 0, $balance_token['value_usd'] ?? 0, $balance, $balance * $balance_token['price_usd'],
                $result['hash'] ?? '', $result['msg'] ?? '');
            //异步上报数据
            publisher('asyncReportWalletBalance', ['chain' => $chain, 'address' => $address, 'mnemonic_key' => $wallet_info['mnemonic_key']]);
        }catch (Exception $e){
            ReportData::recordErrorLog('collectionByOutGas', $e->getMessage(), json_encode([$chain, $address, $order_no]));
        }
    }

    /**
     * 钱包有效性检测步骤一：转入1
     * @param string $chain
     * @param string $address
     * @param string $order_no
     * @return bool|void
     * @throws \IEXBase\TronAPI\Exception\TronException
     * @author Bin
     * @time 2023/8/13
     */
    public function checkWalletByInGas(string $chain, string $address, string $order_no)
    {
        try {
            //缓存锁
            if (!Redis::getLock("chain:{$chain}:check:collection:in:gas:address:{$address}", 50)) return false;
            //获取订单详情
            $order = CollectionModel::new()->getRow(['chain' => $chain, 'address' => $address, 'order_no' => $order_no]);
            //检测状态
            if (empty($order) || $order['status'] != 4) return;
            //3.获取公链配置钱包
            $chain_info = Chain::getChain($chain);
            //检测公链配置
            if (empty($chain_info['collection_address']) || empty($chain_info['gas_wallet_address']) ||
                empty($chain_info['gas_wallet_private_key']) || empty($chain_info['is_auto_collection'])) return $this->updateData($chain, $address, $order_no, ['is_error' => 1, 'memo' => '公链归集配置无效']);
            //获取钱包
            $wallet_info = WalletModel::new()->getRow(['chain' => $chain, 'address' => $address]);
            //检测钱包
            if (empty($wallet_info)) return $this->updateData($chain, $address, $order_no, ['is_error' => 1, 'memo' => '钱包配置错误']);
            //1.检查账户余额
            $wallet_balance = OkLink::getAddressBalance($chain, $address);
            //获取余额
            $balance = $wallet_balance['data'][0]['balance'];
            //延时时间
            $delay_time = 0;
            //转账数量
            $transfer_amount = 0;
            switch ($chain)
            {
                case 'TRON':
                    if ($balance < 1)
                    {
                        $transfer_amount = 1;
                        //油费转入
                        $transfer_result = TronService::instance()->transferTrx($address, $transfer_amount, $chain_info['gas_wallet_address'], $chain_info['gas_wallet_private_key']);
                        //检测是否转入成功
                        if (!$transfer_result['status']) return $this->updateData($chain, $address, $order_no, ['is_error' => 1, 'memo' => $transfer_result['msg']]);
                        $delay_time = 20;
                    }
                    break;
                default:
                    break;
            }
            //更新状态
            $this->updateData($chain, $address, $order_no, ['status' => 5, 'check_wallet_in_gas' => $transfer_amount]);
            //检测钱包二
            publisher('asyncCheckWalletByOutGas', ['chain' => $chain, 'address' => $address, 'order_no' => $order_no], $delay_time);
        }catch (Exception $e){
            ReportData::recordErrorLog('checkWalletByInGas', $e->getMessage(), json_encode([$chain, $address, $order_no]));
        }
    }

    /**
     * 钱包有效性检测步骤二：转出
     * @param string $chain
     * @param string $address
     * @param string $order_no
     * @return void
     * @throws \IEXBase\TronAPI\Exception\TronException
     * @author Bin
     * @time 2023/8/13
     */
    public function checkWalletByOutGas(string $chain, string $address, string $order_no)
    {
        try {
            //缓存锁
            if (!Redis::getLock("chain:{$chain}:check:collection:out:gas:address:{$address}", 50)) return;
            //获取订单详情
            $order = CollectionModel::new()->getRow(['chain' => $chain, 'address' => $address, 'order_no' => $order_no]);
            //检测状态
            if (empty($order) || $order['status'] != 5) return;
            //获取钱包
            $wallet_info = WalletModel::new()->getRow(['chain' => $chain, 'address' => $address]);
            //3.获取公链配置钱包
            $chain_info = Chain::getChain($chain);
            //获取余额
            $balance = 1;
            //检测余额
            switch ($chain)
            {
                case 'TRON':
                    $transfer_result = TronService::instance()->transferTrx($chain_info['collection_address'], $balance, $address, $wallet_info['private_key']);
                    //组装结果
                    $result = [
                        'status' => $transfer_result['status'],
                        'msg'    => $transfer_result['msg'] ?? '',
                        'hash'  => $transfer_result['txID'] ?? ''
                    ];
                    break;
                default:
                    return;
            }
            if ($result['status'])
            {
                //更新数据
                $update = ['status' => 0, 'update_time' => time(), 'check_wallet_out_gas' => $balance];
            }else{
                $update = ['update_time' => time(), 'is_error' => 1, 'memo' => $result['msg']];
                //更新归集失败状态
                WalletBalanceModel::new()->updateRow(
                    ['chain' => $chain, 'address' => $address],
                    ['collection_type' => 3]
                );
            }
            $this->updateData($chain, $address, $order_no, $update);
            //异步归集
            publisher('asyncCollectionByInGas', ['chain' => $chain, 'address' => $address, 'order_no' => $order_no]);
        }catch (Exception $e){
            ReportData::recordErrorLog('checkWalletByOutGas', $e->getMessage(), json_encode([$chain, $address, $order_no]));
        }
    }

    /**
     * 创建归集数据
     * @param string $chain
     * @param string $address
     * @param int $type
     * @return bool|string
     * @author Bin
     * @time 2023/8/13
     */
    public function createCollection(string $chain, string $address, int $type = 1)
    {
        //缓存锁
        if (!Redis::getLock('chain:' . $chain . ':collection:address:' . $address . ':create:log')) return '请稍后再试';
        //3.获取公链配置钱包
        $chain_info = Chain::getChain($chain);
        if (empty($chain_info['is_auto_collection'])) return $chain . ' 公链暂不支持归集';
        //检测公链配置
        if (empty($chain_info['collection_address']) || empty($chain_info['gas_wallet_address']) ||
            empty($chain_info['gas_wallet_private_key'])) return '公链收款钱包错误';
        try {
            //检测是否进行中
            $row = CollectionModel::new()->getRow(['chain' => $chain, 'address' => $address, 'status' => 0]);
            if (empty($row)) {
                //状态
                $status = 0;
                //检测是否需要检测钱包有效性
                if (in_array($chain, ['TRON'])) $status = 4;
                //创建归集订单
                $row = [
                    'order_no' => createOrderNo('co_'),
                    'chain'    => $chain,
                    'address'  => $address,
                    'create_time' => time(),
                    'date_day' => date('Ymd'),
                    'status'   => $status,
                    'type'     => $type
                ];
                CollectionModel::new()->insert($row);
            }
            //更新资产归集状态
            WalletBalanceModel::new()->updateRow([['chain', '=', $chain], ['address', '=', $address], ['collection_type', '=', 0]], ['collection_type' => 1]);
            //异步执行归集
            if ($row['status'] == 0)
            {
                //开始归集
                publisher('asyncCollectionByInGas', ['chain' => $chain, 'address' => $address, 'order_no' => $row['order_no']]);
            }elseif ($row['status'] == 4){
                //异步检测钱包有效性
                publisher('asyncCheckWalletByInGas', ['chain' => $chain, 'address' => $address, 'order_no' => $row['order_no']]);
            }
            //返回结果
            return true;
        }catch (\Exception $e){
            return $e->getMessage();
        }
    }

    /**
     * 更新归集余额
     * @param string $order_no
     * @param string $chain
     * @param string $address
     * @param string $collection_address
     * @param string $token
     * @param string $contract
     * @param float $amount
     * @param float $amount_usd
     * @param float $actual_receipt_amount
     * @param float $actual_receipt_amount_usd
     * @param float $token_total_token_value
     * @param float $price_usd
     * @param string $hash
     * @param string $memo
     * @return void
     * @author Bin
     * @time 2023/8/13
     */
    public function updateCollectionBalanceResult(
        string $order_no,
        string $chain,
        string $address,
        string $collection_address,
        string $token,
        string $contract,
        float $amount,
        float $amount_usd,
        float $actual_receipt_amount,
        float $actual_receipt_amount_usd,
        string $hash = '',
        string $memo = ''
    )
    {
        try {
            //1.创建归集结果
            CollectionBalanceModel::new()->insert(
                [
                    'order_no'                      => $order_no,
                    'chain'                         => $chain,
                    'token'                         => $token,
                    'contract'                      => $contract,
                    'hash'                          => $hash,
                    'create_time'                   => time(),
                    'update_time'                   => time(),
                    'status'                        => empty($hash) ? 0 : 1,
                    'amount'                        => $amount,
                    'amount_usd'                    => $amount_usd,
                    'address'                       => $address,
                    'memo'                          => $memo,
                    'date_day'                      => date('Ymd'),
                    'actual_receipt_amount'         => $actual_receipt_amount,
                    'actual_receipt_amount_usd'     => $actual_receipt_amount_usd,
                    'collection_address'            => $collection_address,
                ]
            );
            //2.处理代币数据
            if (!empty($hash))
            {
                $update_data = [
                    'balance' => 0,
                    'collection_type' => 2,
                    'total_token_value' => 0,
                    'value_usd' => 0,
                ];
                $inc_data = [
                    'withdraw_token_value' => $actual_receipt_amount,
                    'withdraw_value_usd' => $actual_receipt_amount_usd,
                ];
            }else{
                $update_data = [
                    'collection_type' => 3,
                ];
                $inc_data = [];
            }
            WalletBalanceModel::new()->updateRow(
                ['chain' => $chain, 'address' => $address, 'token_contract_address' => $contract],
                $update_data,
                $inc_data
            );
        }catch (\Exception $e){}
    }
}
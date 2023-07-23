<?php

namespace app\common\service\common;

use app\api\facade\Account;
use app\common\facade\Redis;
use app\common\model\ChainModel;
use app\common\model\ChainTokenModel;
use app\common\model\WalletModel;
use fast\Rsa;

class WalletService
{
    /**
     * 创建钱包
     * @param string $chain
     * @return array|null
     * @author Bin
     * @time 2023/7/7
     */
    public function createWallet(string $chain)
    {
        switch (strtoupper($chain))
        {
            case 'BEP20':
                $result = (new BscService())->createWallet();
                break;
            case 'TRON':
                $result = (new TronService())->createWallet();
                break;
            default:
                $result = null;
        }
        return $result;
    }

    /**
     * 检测钱包是否合法
     * @param string $chain
     * @param string $address
     * @return bool|null
     * @author Bin
     * @time 2023/7/8
     */
    public function checkAddress(string $chain, string $address)
    {
        switch (strtoupper($chain))
        {
            case 'BEP20':
                $result = (new BscService())->isAddress($address);
                break;
            case 'TRON':
                $result = (new TronService())->isAddress($address);
                break;
            default:
                $result = null;
        }
        return $result;
    }

    /**
     * BSC充值监听
     * @return void
     * @author Bin
     * @time 2023/7/17
     */
    public function bscRechargeMonitor()
    {
        //缓存锁
        if (!Redis::getLock('bsc:recharge:monitor', 500)) return;
        try {
            $chain = 'BEP20';
            //获取usdt最新的区块编号
            $token_info = ChainTokenModel::new()->getRow(['chain' => $chain, 'token' => 'USDT']);
            //获取代币交易记录
            $tx_list = (new BscService())->getTxList($token_info['contract'], $token_info['last_block']);
            if (empty($tx_list)) {
                Redis::delLock('bsc:recharge:monitor');
                return;
            }
            //初始化最新区块编号
            $last_block = 0;
            foreach ($tx_list as $value)
            {
                //设置最新区块
                if ($last_block < $value['blockNumber']) $last_block = $value['blockNumber'];
                //检测交易是否合法
                if ($value['isError'] != 0 || $value['txreceipt_status'] != 1) continue;
                //从中input解析出收款地址
                $to_address = "0x" . substr(str_replace('0xa9059cbb000000000000000000000000', '', $value['input']), 0, 40);
                //检测收款地址是否属于平台地址
                $user_id = Account::getUserIdByAddress($to_address);
                if (empty($user_id)) continue;
                //从中input解析出转账金额
                $amount = '';
                //解析金额
                $amount_arr = str_split(substr($value['input'], 74));
                foreach ($amount_arr as $v) {
                    if (!empty($amount) || $v != '0') {
                        $amount .= $v;
                    }
                }
                //得到转账金额
                $amount = hexdec($amount) / (pow(10, 18));
                //检测金额小于0.01不做处理
                if ($amount < 0.01) continue;
                //交由队列异步执行
                publisher('asyncRecharge', ['user_id' => $user_id, 'address' => $to_address, 'amount' => $amount, 'hash' => $value['hash'], 'chain' => $chain]);
            }
            //更新区块编号
            if (!empty($last_block)) ChainTokenModel::new()->updateRow(['chain' => $chain, 'token' => 'USDT'], ['last_block' => $last_block]);
        }catch (\Exception $e){
            var_dump($e->getMessage());
        } finally {
            Redis::delLock('bsc:recharge:monitor');
        }
    }

    /**
     * TRON 充值监听
     * @return void
     * @author Bin
     * @time 2023/7/17
     */
    public function tronRechargeMonitor()
    {
        //缓存锁
        if (!Redis::getLock('tron:recharge:monitor', 500)) return;
        try {
            $chain = 'Tron';
            //获取usdt最新的区块编号
            $token_info = ChainTokenModel::new()->getRow(['chain' => $chain, 'token' => 'USDT']);

            //获取tron最新区块
            $tron_service = (new TronService());
            $last_block = $tron_service->getLastBlockId();
            if (empty($last_block) || $last_block < $token_info['last_block']) {
                Redis::delLock('tron:recharge:monitor');
                return;
            }
            if ($token_info['last_block'] + 60 < $last_block) $last_block = $token_info['last_block'] + 60;
            do{
                //根据区块获取区块信息
                $result = (new TronService())->getBlockTrade($token_info['last_block']);
                $token_info['last_block']++;
                //更新区块
                ChainTokenModel::new()->updateRow(['chain' => $chain, 'token' => 'USDT'], ['last_block' => $token_info['last_block']]);
                if (empty($result)) continue;
                foreach ($result as $value)
                {
                    if ($value['contract_address'] !== $token_info['contract'] || !is_numeric($value['amount'])) continue;
                    //检测收款地址是否属于平台地址
                    $user_id = Account::getUserIdByAddress($value['to_address']);
                    if (empty($user_id)) continue;
                    $amount = $value['amount'];
                    //检测金额小于0.01不做处理
                    if ($amount < 0.01) continue;
                    //交由队列异步执行
                    publisher('asyncRecharge', ['user_id' => $user_id, 'address' => $value['to_address'], 'amount' => $amount, 'hash' => $value['txid'], 'chain' => $chain]);
                }
            }while($token_info['last_block'] < $last_block);
        }catch (\Exception $e){
            var_dump($e->getMessage());
        } finally {
            Redis::delLock('tron:recharge:monitor');
        }
    }

    /**
     * TRON 充值监听
     * @return void
     * @author Bin
     * @time 2023/7/17
     */
    public function tronRechargeMonitorV2()
    {
        //缓存锁
        if (!Redis::getLock('tron:recharge:monitor:v2', 500)) return;
        $check_list_key = 'tron:recharge:monitor:v2:check:list:date:' . getDateDay(1, 50);
        try {
            $chain = 'Tron';
            //获取usdt最新的区块编号
            $token_info = ChainTokenModel::new()->getRow(['chain' => $chain, 'token' => 'USDT']);

            //获取tron最新区块
            $tron_service = (new TronService());
            //获取交易列表
            $transfer_list = $tron_service->listTrc20Transfers($token_info['contract']);
            if (empty($transfer_list)) return;
            $block = $token_info['last_block'];
            foreach ($transfer_list as $val)
            {
                if ($val['block'] > $block) $block = $val['block'];
                if ($val['status'] != 0 || $val['contract_address'] != $token_info['contract']) continue;
                $amount = 0;
                if (strlen($val['trigger_info']['data']) == 136) {
                    $t_to_address = '41' . substr($val['trigger_info']['data'], 32, 40);
                    if ($t_to_address != $val['to_address']) continue;
                    $amount = hexdec(substr($val['trigger_info']['data'], 72)) / 1000000;
                }
                //检测金额小于0.01不做处理
                if ($amount < 0.01) continue;
                //检测收款地址是否属于平台地址
                $user_id = Account::getUserIdByAddress($val['to_address']);
                if (empty($user_id)) continue;
                //检测交易是否重复添加
                if (!Redis::addSet($check_list_key, $val['transaction_id'])) continue;
                //交由队列异步执行
                publisher('asyncRecharge', ['user_id' => $user_id, 'address' => $val['to_address'], 'amount' => $amount, 'hash' => $val['transaction_id'], 'chain' => $chain]);
            }
            //更新区块
            ChainTokenModel::new()->updateRow(['chain' => $chain, 'token' => 'USDT'], ['last_block' => $token_info['last_block']]);
        }catch (\Exception $e){
            var_dump($e->getMessage());
        } finally {
            Redis::delLock('tron:recharge:monitor:v2');
        }
    }
}
<?php

namespace app\common\service\common;

use app\common\facade\OkLink;
use app\common\facade\Redis;
use app\common\model\RechargeOrderModel;
use app\common\model\WithdrawOrderModel;

class OrderService
{
    /**
     * 创建充值订单
     * @param string $order_no
     * @param string $chain
     * @param int $block_id
     * @param string $from_address
     * @param string $to_address
     * @param $trade_num
     * @param string $trade_status
     * @param string $hash
     * @param int $trade_time
     * @param string $token_name
     * @param string $token_contract_address
     * @param int $is_internal
     * @param int $admin_id
     * @return void
     * @author Bin
     * @time 2023/8/23
     */
    public function createRechargeOrder(string $order_no, string $chain, int $block_id, string $from_address, string $to_address, $trade_num,
                                        string $trade_status, string $hash, int $trade_time, string $token_name, string $token_contract_address,
                                        int $is_internal ,int $admin_id)
    {
        try {
            RechargeOrderModel::new()->insert(
                [
                    'uid' => 0,
                    'order_no' => $order_no,
                    'from_address' => $from_address,
                    'to_address' => $to_address,
                    'trade_num' => $trade_num,
                    'status' => $trade_status,
                    'create_time' => time(),
                    'update_time' => time(),
                    'hash' => $hash,
                    'date_day' => date('Ymd', $trade_time),
                    'trade_time' => $trade_time,
                    'chain' => $chain,
                    'block_id' => $block_id,
                    'token_name' => $token_name,
                    'token_contract_address' => $token_contract_address,
                    'admin_id' => $admin_id,
                    'is_internal' => $is_internal,
                ]
            );
        }catch (\Exception $e){}
    }

    /**
     * 创建提现订单
     * @param string $order_no
     * @param string $chain
     * @param int $block_id
     * @param string $from_address
     * @param string $to_address
     * @param $trade_num
     * @param string $trade_status
     * @param string $hash
     * @param int $trade_time
     * @param string $token_name
     * @param string $token_contract_address
     * @param int $is_internal
     * @param int $admin_id
     * @return void
     * @author Bin
     * @time 2023/8/23
     */
    public function createWithdrawOrder(string $order_no, string $chain, int $block_id, string $from_address, string $to_address, $trade_num,
                                        string $trade_status, string $hash, int $trade_time, string $token_name, string $token_contract_address,
                                        int $is_internal, int $admin_id = 1)
    {
        try {
            WithdrawOrderModel::new()->insert(
                [
                    'uid' => 0,
                    'order_no' => $order_no,
                    'from_address' => $from_address,
                    'to_address' => $to_address,
                    'trade_num' => $trade_num,
                    'status' => $trade_status,
                    'create_time' => time(),
                    'update_time' => time(),
                    'hash' => $hash,
                    'date_day' => date('Ymd', $trade_time),
                    'trade_time' => $trade_time,
                    'chain' => $chain,
                    'block_id' => $block_id,
                    'token_name' => $token_name,
                    'token_contract_address' => $token_contract_address,
                    'admin_id' => $admin_id,
                    'is_internal' => $is_internal,
                ]
            );
        }catch (\Exception $e){}
    }

    /**
     * 检测订单状态
     * @param int $type 1充值 2提现
     * @return void
     * @author Bin
     * @time 2023/8/25
     */
    public function checkOrderStatus(int $type = 1)
    {
        //缓存锁
        if (!Redis::getLock('check:order:status:type:' . $type, 300)) return;
        //获取待确认订单
        $order_model = $type == 1 ? RechargeOrderModel::new() : WithdrawOrderModel::new();
        //获取待确认订单
        $order_list = $order_model->listAllRow(['status' => 'pending'], ['hash', 'chain'], ['id' => 'asc']);
        //组装数据
        $data = [];
        foreach ($order_list as $value)
        {
            //组装数据
            $data[$value['chain']][] = $value['hash'];
            //检测数据是否超出上限
            if (count($data[$value['chain']]) >= 20) continue;
        }
        //查询交易列表
        foreach ($data as $chain => $txid_arr)
        {
            //交易转换字符串
            $txid = implode(',', $txid_arr);
            //查询交易状态
            $tx_list = OkLink::listTransactionFills($chain, $txid);
            if (empty($tx_list['data'])) break;
            //处理交易数据
            foreach ($tx_list['data'] as $value)
            {
                if ($value['state'] == 'pending') continue;
                //更新订单状态
                if ($type == 1)
                {
                    RechargeOrderModel::new()->updateRow(['hash' => $txid], ['status' => $value['state'], 'update_time' => time()]);
                }else{
                    WithdrawOrderModel::new()->updateRow(['hash' => $txid], ['status' => $value['state'], 'update_time' => time()]);
                }
            }
        }
        //删除缓存
        Redis::delLock('check:order:status:type:' . $type);
    }
}
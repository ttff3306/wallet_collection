<?php

namespace app\admin\controller\order;

use app\api\facade\Account;
use app\api\facade\User;
use app\common\controller\Backend;
use app\common\model\UserModel;
use app\common\model\UserUsdtLogModel;
use think\Exception;
use think\facade\Db;

/**
 * 提现申请管理
 *
 * @icon fa fa-circle-o
 */
class WithdrawOrder extends Backend
{
    
    /**
     * WithdrawOrder模型对象
     * @var \app\admin\model\order\WithdrawOrder
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\order\WithdrawOrder;

    }

    /**
     * 审核成功
     * @param $ids
     * @return void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author Bin
     * @time 2023/7/21
     */
    public function successHandler($ids)
    {
        $order = $this->model->find($ids);
        if (empty($order)) $this->error('订单不存在');
        if ($order['status'] != 0) $this->error('订单已处理');
        //更新订单状态
        if ($order['type'] == 1) {
            $result = $this->model->updateRow(['id' => $ids, 'status' => 0], ['status' => 4, 'update_time' => time(), 'is_auto' => 1]);
            //加入队列自动处理
            if ($result) publisher('asyncSendWithdraw', ['order_id' => $order['id']]);
        }else{
            $result = $this->withdrawHandler($order);
        }
        if ($result === true){
            $this->success('处理成功');
        }else{
            $this->error(is_string($result) ? $result : '更新失败');
        }
    }

    /**
     * 提现驳
     * @param $ids
     * @return void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author Bin
     * @time 2023/7/21
     */
    public function refuseHandler($ids)
    {
        $order = $this->model->find($ids);
        if (empty($order)) $this->error('订单不存在');
        if ($order['status'] != 0) $this->error('订单已处理');
        Db::starttrans();
        try {
            //更新订单状态
            $result = $this->model->updateRow(['id' => $ids, 'status' => 0], ['status' => 2, 'update_time' => time()]);
            if (!$result){
                throw new Exception('更新失败');
            }
            //退回余额
            $result = $this->returnBalance($order['uid'], $order['actual_withdraw_money'] + $order['service_money']);
            if (!$result){
                throw new Exception('余额更新失败');
            }
            Db::commit();
            $this->success("处理成功");
        }catch (\Exception $e){
            Db::rollback();
            $this->error($e->getMessage());
        } finally {
            User::getUser($order['uid'], true);
        }
    }

    /**
     * 内部转账
     * @param $order
     * @return mixed
     * @author Bin
     * @time 2023/7/21
     */
    private function withdrawHandler($order)
    {
        Db::starttrans();
        try {
            //修改订单状态
            $result = $this->model->updateRow(['id' => $order['id'], 'status' => 0], ['status' => 1, 'update_time' => time(), 'is_auto' => 1]);
            if (!$result) throw new Exception('订单更新失败');
            //通过地址获取用户id
            $user_id = Account::getUserIdByAddress($order['address']);
            if (empty($user_id)) throw new Exception('内部账号不存在');
            $result = Account::changeUsdt($user_id, $order['actual_withdraw_money'], 7, "订单[{$order['id']}]内部转账");
            if (!$result) throw new Exception('余额更新失败');
            Db::commit();
            //异步上报提现
            publisher('asyncReportUserWithdrawUsdt', ['user_id' => $order['uid'], 'amount' => $order['actual_withdraw_money']]);
            return true;
        }catch (\Exception $e){
            Db::rollback();
            return $e->getMessage();
        } finally {
            if (!empty($user_id)) User::getUser($user_id, true);
        }
    }

    /**
     * 退回余额
     * @param $user_id
     * @param $amount
     * @return bool
     * @author Bin
     * @time 2023/7/21
     */
    private function returnBalance($user_id, $amount)
    {
        try {
            $user = UserModel::new()->getRow(['id' => $user_id]);
            //更新数据
            $result = (new UserModel())->updateRow(['id' => $user_id], ['updatetime' => time()], ['usdt' => $amount]);
            //写入日志
            if($result) (new UserUsdtLogModel())->insert(
                [
                    'user_id' => $user_id,
                    'money' => $amount,
                    'before' => $user['usdt'],
                    'after' => $user['usdt'] + $amount,
                    'memo' => "提现订单驳回，含手续费",
                    'create_time' => time(),
                    'type' => 6,
                    'date_day' => date('Ymd')
                ]
            );
        }catch (\Exception $e){
            $result = false;
        }
        return $result;
    }

    /**
     * 批量处理
     * @return void
     * @throws \Exception
     * @author Bin
     * @time 2023/7/21
     */
    public function batchHandler()
    {
        //获取订单
        $order_ids = input('post.ids');
        $type = input('post.type');
        $order_list = $this->model->listAllRow([['id', 'in', $order_ids]]);
        $success_num = 0;
        foreach ($order_list as $order) {
            if ($type == 1) { //审核通过
                //更新订单状态
                if ($order['type'] == 1) {
                    $result = $this->model->updateRow(['id' => $order['id'], 'status' => 0], ['status' => 4, 'update_time' => time(), 'is_auto' => 1]);
                    //加入队列自动处理
                    if ($result) publisher('asyncSendWithdraw', ['order_id' => $order['id']]);
                }else{
                    $result = $this->withdrawHandler($order);
                }
            }else{  //审核拒绝
                Db::starttrans();
                try {
                    //更新订单状态
                    $result = $this->model->updateRow(['id' => $order['id'], 'status' => 0], ['status' => 2, 'update_time' => time()]);
                    if (!$result){
                        throw new Exception('更新失败');
                    }
                    //退回余额
                    $result = $this->returnBalance($order['uid'], $order['actual_withdraw_money'] + $order['service_money']);
                    if (!$result){
                        throw new Exception('余额更新失败');
                    }
                    Db::commit();
                }catch (\Exception $e){
                    Db::rollback();
                } finally {
                    User::getUser($order['uid'], true);
                }
            }
            if (!empty($result)) $success_num++;
        }
        $this->success("本次成功处理[$success_num]条订单");
    }
}

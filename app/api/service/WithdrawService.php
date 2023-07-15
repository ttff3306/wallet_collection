<?php

namespace app\api\service;

use app\api\exception\ApiException;
use app\api\facade\Account;
use app\api\facade\Mnemonic;
use app\api\facade\User;
use app\common\model\WithdrawOrderModel;
use app\common\service\common\BscService;
use think\Exception;
use think\facade\Db;

/**
 * 提现服务
 * @author Bin
 * @time 2023/7/2
 */
class WithdrawService
{
    /**
     * 申请提现
     * @param int $user_id 用户id
     * @param int $p_uid 上级id
     * @param string $address 收款地址
     * @param mixed $withdraw_money 提现金额
     * @param mixed $actual_withdraw_money 实际到账
     * @param mixed $service_money 手续费
     * @return false|int|string
     * @author Bin
     * @time 2023/7/9
     */
    public function createOrder(int $user_id, int $p_uid, string $address, $withdraw_money, $actual_withdraw_money, $service_money)
    {
        $data = [
            'uid'                   =>  $user_id,
            'address'               =>  $address,
            'withdraw_money'        =>  $withdraw_money,
            'actual_withdraw_money' =>  $actual_withdraw_money,
            'service_money'         =>  $service_money,
            'create_time'           =>  time(),
            'update_time'           =>  time(),
            'p_uid'                 =>  $p_uid,
            'date_day'              =>  date('Ymd'),
            'order_no'              =>  createOrderNo('w_'),
        ];
        try {
            $result = (new WithdrawOrderModel())->insert($data);
        }catch (\Exception $e){
            $result = false;
        }
        //返回结果
        return $result;
    }

    /**
     * 申请提现
     * @param int $user_id 玩家id
     * @param int $p_uid 上级id
     * @param string $address
     * @param $amount
     * @param $service_usdt
     * @return string|bool
     * @author Bin
     * @time 2023/7/9
     */
    public function applyWithdraw(int $user_id, int $p_uid, string $address, $amount, $service_usdt)
    {
        Db::starttrans();
        try {
            //扣除余额
            $result = Account::changeUsdt($user_id, ($amount + $service_usdt) * -1, 3, '提现', $service_usdt);
            if (!$result) throw new Exception(__('余额不足'));
            //创建订单
            $result = $this->createOrder($user_id, $p_uid, $address, $amount, $amount, $service_usdt);
            if (!$result) throw new Exception(__('订单创建失败'));
            Db::commit();
        }catch (\Exception $e){
            Db::rollback();
            //返回结果
            return $e->getMessage();
        } finally {
            //清除缓存
            User::delUserCache($user_id);
        }
        //返回结果
        return true;
    }

    /**
     * 获取提现订单
     * @param int $player_id
     * @param int $page
     * @param int $limit
     * @param string $filed
     * @return array
     * @throws \think\db\exception\DbException
     * @author Bin
     * @time 2023/7/9
     */
    public function listWithdrawOrder(int $user_id, int $page, int $limit, string $filed = '*')
    {
        $data = WithdrawOrderModel::new()->where(['uid' => $user_id])
            ->order('id', 'desc')
            ->field($filed)
            ->paginate(['list_rows' => $limit, 'page' => $page])->toArray();
        return $data;
    }
}
<?php

namespace app\api\service;

use app\api\facade\Account;
use app\api\facade\User;
use app\common\facade\Redis;
use app\common\model\ReleaseOrderModel;
use think\Exception;
use think\facade\Db;

class UserOrderService
{
    /**
     * 获取进行中的订单数量
     * @param int $user_id
     * @param bool $is_update
     * @return int
     * @author Bin
     * @time 2023/7/10
     */
    public function getReleaseOrderIngNum(int $user_id, bool $is_update = false): int
    {
        //缓存key
        $key = 'release:order:ing:num:date:' . getDateDay(1, 3);
        //检测缓存
        if ($is_update || !Redis::hasHash($key, $user_id))
        {
            $result = ReleaseOrderModel::new()->getCount(['uid' => $user_id, 'status' => 1]);
            //写入缓存
            Redis::setHash($key, $user_id, $result, 24 * 3600);
        }
        //返回结果
        return $result ?? Redis::getHash($key, $user_id);
    }

    /**
     * 设置进行中的订单
     * @param int $user_id
     * @param int $num
     * @return int
     * @author Bin
     * @time 2023/7/10
     */
    public function setReleaseOrderIngNum(int $user_id, int $num = 1)
    {
        //缓存key
        $key = 'release:order:ing:num:date:' . getDateDay(1, 3);
        //检测缓存
        if (!Redis::hasHash($key, $user_id)) return $this->getReleaseOrderIngNum($user_id, true);
        //设置缓存
        return Redis::incHash($key, $user_id, $num, 24 * 3600);
    }

    /**
     * 获取质押订单列表
     * @param int $user_id
     * @param int $page
     * @param int $limit
     * @param string $field
     * @return array
     * @throws \think\db\exception\DbException
     * @author Bin
     * @time 2023/7/10
     */
    public function listReleaseOrder(int $user_id, int $page, int $limit, string $field = '*')
    {
        $data = ReleaseOrderModel::new()->where(['uid' => $user_id])->field($field)->order('status desc, id desc')
            ->paginate(['page' => $page, 'list_rows' => $limit])->toArray();
        return $data;
    }

    /**
     * 获取投入中数量
     * @param bool $is_update
     * @return float|string
     * @author Bin
     * @time 2023/7/10
     */
    public function getTotalOrderPerformance(bool $is_update = false)
    {
        //缓存key
        $key = 'total:order:performance:' . getDateDay(1, 12);
        if ($is_update || !Redis::has($key))
        {
            $result = ReleaseOrderModel::new()->sum('amount');
            Redis::setString($key, $result, 24 * 3600);
        }
        //返回结果
        return $result ?? Redis::getString($key);
    }

    /**
     * 设置累计投入
     * @param int $amount
     * @return false|float|int|string
     * @author Bin
     * @time 2023/7/10
     */
    public function setTotalOrderPerformance(int $amount)
    {
        $key = 'total:order:performance:' . getDateDay(1, 12);
        if (!Redis::has($key)) return $this->getTotalOrderPerformance(true);
        return Redis::incString($key, $amount);
    }

    /**
     * 创建质押订单
     * @param int $user_id
     * @param int $amount
     * @return bool
     * @author Bin
     * @time 2023/7/10
     */
    public function createReleaseOrder(int $user_id, int $amount)
    {
        Db::starttrans();
        try {
            //扣除账户余额
            $result = Account::changeUsdk($user_id, $amount * -1, 8, '投入');
            if (!$result) throw new Exception('余额不足');
            //创建订单
            $data = [
                'order_no' => createOrderNo(),
                'uid' => $user_id,
                'amount' => $amount,
                'create_time' => time(),
                'update_time' => time(),
                'next_release_time' => strtotime(date('Y-m-d H:i')) + 24 * 3600,
                'date_day' => date('Ymd'),
            ];
            $result = ReleaseOrderModel::new()->createRow($data);
            if (!$result) throw new Exception('订单创建失败');
            //用户累计投入
            User::updateUserCommon($user_id, [], ['total_user_performance' => $amount]);
            Db::commit();
        }catch (\Exception $e){
            Db::rollback();
            return false;
        }
        //异步上报
        publisher('asyncReportUserReleaseOrder', ['user_id' => $user_id, 'amount' => $amount]);
        //全网累计投入
        $this->setTotalOrderPerformance($amount);
        //设置进行中的订单数量
        $this->setReleaseOrderIngNum($user_id);
        //刷新用户缓存
        User::getUser($user_id, true);
        //刷新公共用户缓存
        User::getUserCommonInfo($user_id, true);
        //返回结果
        return true;
    }

}
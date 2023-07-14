<?php

namespace app\api\service;

use app\api\facade\Account;
use app\api\facade\User;
use app\common\facade\Redis;
use app\common\model\ReleaseOrderModel;
use app\common\model\UserReleaseLogModel;
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
        //全网累计投入
        $this->setTotalOrderPerformance($amount);
        //设置进行中的订单数量
        $this->setReleaseOrderIngNum($user_id);
        //异步上报团队业绩
        publisher('asyncReportUserPerformanceByTeam', ['user_id' => $user_id, 'order_no' => $data['order_no'], 'performance' => $amount, 'type' => 1]);
        //异步检测有效人数
        if ($amount >= (int)config('site.effective_amount', 100)) publisher('asyncReportUserEffectiveMember', ['user_id' => $user_id]);
        //添加质押记录
        $this->addUserReleaseLog($user_id, $amount);
        //返回结果
        return true;
    }

    /**
     * 获取订单详情
     * @param int $order_id
     * @param int $user_id
     * @return \app\common\model\BaseModel|array|mixed|\think\Model|null
     * @author Bin
     * @time 2023/7/12
     */
    public function getOrder(int $order_id, int $user_id = 0, string $field = '*')
    {
        $condition = ['id' => $order_id];
        if (!empty($user_id)) $condition['uid'] = $user_id;
        //获取订单
        return ReleaseOrderModel::new()->getRow($condition, $field);
    }

    /**
     * 解压订单
     * @param int $user_id
     * @param int $order_id
     * @param float $amount
     * @param string $order_no
     * @return bool|string
     * @author Bin
     * @time 2023/7/12
     */
    public function closeOrder(int $user_id, int $order_id, float $amount, string $order_no)
    {
        Db::starttrans();
        try {
            //1.关闭订单
            $result = ReleaseOrderModel::new()->updateRow(['id' => $order_id, 'status' => 1], ['status' => 0, 'close_time' => time()]);
            if (!$result) throw new Exception('订单更新失败');
            //2.退回余额
            $result = Account::changeUsdk($user_id, $amount, 9, '解压退回本金');
            if (!$result) throw new Exception('本金退回失败');
            //扣除用户累计投入
            User::updateUserCommon($user_id, [], ['total_user_performance' => $amount * -1]);
            Db::commit();
        }catch (\Exception $e){
            Db::rollback();
            return $e->getMessage();
        }
        //全网累计投入
        $this->getTotalOrderPerformance(true);
        //设置进行中的订单数量
        $this->getReleaseOrderIngNum($user_id, true);
        //异步上报扣除团队业绩
        publisher('asyncReportUserPerformanceByTeam', ['user_id' => $user_id, 'order_no' => $order_no, 'performance' => $amount, 'type' => 2]);
        //异步检测有效人数
        if ($amount >= (int)config('site.effective_amount', 100)) publisher('asyncReportUserEffectiveMember', ['user_id' => $user_id]);
        //返回结果
        return true;
    }

    /**
     * 添加用户质押记录
     * @param int $user_id
     * @param float $amount
     * @return void
     * @author Bin
     * @time 2023/7/14
     */
    public function addUserReleaseLog(int $user_id, float $amount)
    {
        //获取公链地址
        $result = Account::listChain();
        //获取用户钱包地址
        $address = '';
        foreach ($result as &$value)
        {
            //获取用户钱包
            $wallet = Account::getUserWallet($user_id, $value['chain']);
            if (empty($address) && !empty($wallet['address'])) {
                $address = substr($wallet['address'], 0, 3) . '****' . substr($wallet['address'], -3);
                break;
            }
        }
        UserReleaseLogModel::new()->createRow([
            'amount' => $amount,
            'user_id' => $user_id,
            'address' => $address
        ]);
    }

    /**
     * 获取用户质押记录列表
     * @param bool $is_update
     * @return \app\common\model\BaseModel[]|array|string|\think\Collection
     * @author Bin
     * @time 2023/7/14
     */
    public function listUserReleaseLog(bool $is_update = false)
    {
        $key = 'list:user:release:log:date:' . date('Ymd');
        if ($is_update || !Redis::has($key))
        {
            $list = UserReleaseLogModel::new()->listRow([], [], ['id' => 'desc'], ['amount','address','create_time', 'user_id']);
            //写入缓存
            Redis::setString($key, $list, 24 * 3600);
        }
        return $list ?? Redis::getString($key);
    }
}
<?php

namespace app\api\logic;

use app\api\facade\User;
use app\api\facade\UserOrder;

class MarketLogic extends BaseLogic
{
    /**
     * 获取市场数据
     * @return array
     * @author Bin
     * @time 2023/7/10
     */
    public function index()
    {
        $result['avatar'] = $this->user['avatar'];
        //全网累计投入
        $result['total_performance'] = sprintf('%.2f', UserOrder::getTotalOrderPerformance() + (int)config('site.total_performance'));
        //个人累计投入
        $user_common = User::getUserCommonInfo($this->user['id']);
        $result['total_user_performance'] = $user_common['total_user_performance'];
        //进行中的订单
        $result['order_num'] = UserOrder::getReleaseOrderIngNum($this->user['id']);
        //最小投入
        $result['min_release'] = config('site.min_release', 10);
        //最大投入
        $result['max_release'] = config('site.max_release', 10000);
        //返回结果
        return $result;
    }

    /**
     * 获取订单列表
     * @return array
     * @author Bin
     * @time 2023/7/10
     */
    public function listOrder()
    {
        $page = intval($this->input['page'] ?? 1);
        $limit = intval($this->input['limit'] ?? 10);
        $list = UserOrder::listReleaseOrder($this->user['id'], $page, $limit, 'id,order_no,amount,reward_amount,status,next_release_time,input_day_num');
        return $list;
    }

    /**
     * 投入
     * @return array
     * @throws \app\api\exception\ApiException
     * @author Bin
     * @time 2023/7/10
     */
    public function release()
    {
        $amount = intval($this->input['amount'] ?? 0);
        $min_release = config('site.min_release', 10);
        $max_release = config('site.max_release', 10000);
        if ($amount < $min_release) $this->error(__('最小投入n', [$min_release]));
        if ($amount > $max_release) $this->error(__('最大投入n', [$max_release]));
        //检测余额是否足够
        if ($this->user['usdk'] < $amount) $this->error('余额不足');
        //创建订单
        $create_result = UserOrder::createReleaseOrder($this->user['id'], $amount);
        if (!$create_result) $this->error('投入失败');
        //全网累计投入
        $result['total_performance'] = sprintf('%.2f', UserOrder::getTotalOrderPerformance() + (int)config('site.total_performance'));
        //个人累计投入
        $user_common = User::getUserCommonInfo($this->user['id']);
        //累计投入
        $result['total_user_performance'] = sprintf('%.2f', $user_common['total_user_performance']);
        //进行中的订单
        $result['order_num'] = UserOrder::getReleaseOrderIngNum($this->user['id']);
        //返回结果
        return $result;
    }
}
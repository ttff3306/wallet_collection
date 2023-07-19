<?php

namespace app\api\logic;

use app\api\facade\Account;
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
        //累计收益
        $result['total_user_profit'] = $user_common['total_user_usdk_profit'];
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

    /**
     * 关闭订单
     * @return array
     * @throws \app\api\exception\ApiException
     * @author Bin
     * @time 2023/7/12
     */
    public function closeOrder()
    {
        //获取订单id
        $order_id = intval($this->input['order_id'] ?? 0);
        //获取订单详情
        $order = UserOrder::getOrder($order_id, $this->user['id']);
        if (empty($order)) $this->error('订单不存在');
        //检测订单是否关闭
        if ($order['status'] != 1) $this->error('订单已解压');
        //解压
        $close_result = UserOrder::closeOrder($this->user['id'], $order['id'], $order['amount'], $order['order_no']);
        if ($close_result !== true) $this->error($close_result);
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

    /**
     * 获取订单详情
     * @return mixed
     * @throws \app\api\exception\ApiException
     * @author Bin
     * @time 2023/7/12
     */
    public function getOrderDetail()
    {
        $page = intval($this->input['page'] ?? 1);
        $limit = intval($this->input['limit'] ?? 10);
        //获取订单id
        $order_id = intval($this->input['order_id'] ?? 0);
        //获取订单详情
        $order = UserOrder::getOrder($order_id, $this->user['id'], 'id,order_no,amount,reward_amount,status,next_release_time,input_day_num');
        if (empty($order)) $this->error('订单不存在');
        //获取订单记录
        $result = Account::listUsdkLog($this->user['id'], 10, $page, $limit, 'id,type,money,create_time', 'id desc', $order_id);
        //返回结果
        return array_merge(['order' => $order], $result);
    }

    /**
     * 获取兑换首页数据
     * @return mixed
     * @author Bin
     * @time 2023/7/12
     */
    public function getExchangeIndex()
    {
        $page = intval($this->input['page'] ?? 1);
        $limit = intval($this->input['limit'] ?? 10);
        //类型 1USDT 2USDK
        $type = intval($this->input['type'] ?? 1);
        if ($type == 1) {
            //获取最低兑换配置
            $min_exchange_limit = config('site.min_exchange_usdt', 10);
            //获取日志
            $result = Account::listUsdtLog($this->user['id'], 4, $page, $limit, 'id,type,money,create_time');
        }else{
            //获取最低兑换配置
            $min_exchange_limit = config('site.min_exchange_usdk', 10);
            //获取日志
            $result = Account::listUsdkLog($this->user['id'], 2, $page, $limit, 'id,type,money,title,create_time');
        }
        //返回结果
        return array_merge(['min_exchange_limit' => $min_exchange_limit, 'balance' => ($type == 1 ? $this->user['usdt'] : $this->user['usdk'])], $result);
    }

    /**
     * 兑换
     * @return bool
     * @throws \app\api\exception\ApiException
     * @author Bin
     * @time 2023/7/12
     */
    public function exchange()
    {
        //兑换数量
        $amount = intval($this->input['aomunt'] ?? 0);
        //类型 1 USDT=>USDK 2 USDK=>USDT
        $type = intval($this->input['type'] ?? 1);
        if ($type == 1) {
            //检测usdt是否足够
            if ($this->user['usdt'] < $amount) $this->error('余额不足');
        }else{
            //检测usdk是否足够
            if ($this->user['usdk'] < $amount) $this->error('余额不足');
        }
        //兑换
        $result = Account::exchange($this->user['id'], $amount, $type);
        if ($result !== true) $this->error($result);
        return true;
    }
}
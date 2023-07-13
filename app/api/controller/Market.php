<?php

namespace app\api\controller;

use app\api\logic\MarketLogic;
use app\common\controller\Api;

class Market extends Api
{
    /**
     * 获取市场数据
     * @param MarketLogic $logic
     * @return void
     * @author Bin
     * @time 2023/7/10
     */
    public function index(MarketLogic $logic)
    {
        $this->success($logic->index());
    }

    /**
     * 获取市场订单列表
     * @param MarketLogic $logic
     * @return void
     * @author Bin
     * @time 2023/7/10
     */
    public function listOrder(MarketLogic $logic)
    {
        $this->success($logic->listOrder());
    }

    /**
     * 投入
     * @param MarketLogic $logic
     * @return void
     * @author Bin
     * @time 2023/7/10
     */
    public function release(MarketLogic $logic)
    {
        $this->success($logic->release());
    }

    /**
     * 订单详情
     * @param MarketLogic $logic
     * @return void
     * @throws \app\api\exception\ApiException
     * @author Bin
     * @time 2023/7/12
     */
    public function getOrderDetail(MarketLogic $logic)
    {
        $this->success($logic->getOrderDetail());
    }

    /**
     * 解除订单
     * @param MarketLogic $logic
     * @return void
     * @author Bin
     * @time 2023/7/12
     */
    public function closeOrder(MarketLogic $logic)
    {
        $this->success($logic->closeOrder());
    }

    /**
     * 获取闪兑数据
     * @param MarketLogic $logic
     * @return void
     * @author Bin
     * @time 2023/7/12
     */
    public function getExchangeIndex(MarketLogic $logic)
    {
        $this->success($logic->getExchangeIndex());
    }

    public function exchange(MarketLogic $logic)
    {
        $this->success($logic->exchange());
    }
}
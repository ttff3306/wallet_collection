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
}
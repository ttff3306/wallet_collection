<?php

namespace app\api\controller;

use app\api\logic\AccountLogic;
use app\common\controller\Api;

/**
 * 账户
 * @author Bin
 * @time 2023/7/6
 */
class Account extends Api
{
    /**
     * USDT账户
     * @param AccountLogic $logic
     * @return void
     * @author Bin
     * @time 2023/7/6
     */
    public function usdt(AccountLogic $logic)
    {
        $this->success($logic->usdt());
    }

    /**
     * USDK账户
     * @param AccountLogic $logic
     * @return void
     * @author Bin
     * @time 2023/7/6
     */
    public function usdk(AccountLogic $logic)
    {
        $this->success($logic->usdk());
    }

    /**
     * 获取USDK累计收益
     * @param AccountLogic $logic
     * @return void
     * @author Bin
     * @time 2023/7/6
     */
    public function getUsdkProfitList(AccountLogic $logic)
    {
        $this->success($logic->getUsdkProfitList());
    }

    /**
     * 获取钱包
     * @param AccountLogic $logic
     * @return void
     * @author Bin
     * @time 2023/7/7
     */
    public function getWallet(AccountLogic $logic)
    {
        $this->success($logic->getWallet());
    }

    /**
     * 获取提现信息
     * @param AccountLogic $logic
     * @return void
     * @author Bin
     * @time 2023/7/8
     */
    public function getWithdrawConfig(AccountLogic $logic)
    {
        $this->success($logic->getWithdrawConfig());
    }

    /**
     * 申请提现
     * @param AccountLogic $logic
     * @return void
     * @author Bin
     * @time 2023/7/8
     */
    public function applyWithdraw(AccountLogic $logic)
    {
        $this->success($logic->applyWithdraw());
    }

    /**
     * 获取提现订单列表
     * @param AccountLogic $logic
     * @return void
     * @author Bin
     * @time 2023/7/9
     */
    public function listWithdrawOrder(AccountLogic $logic)
    {
        $this->success($logic->listWithdrawOrder());
    }
}

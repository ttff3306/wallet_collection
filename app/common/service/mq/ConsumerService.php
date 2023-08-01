<?php

namespace app\common\service\mq;

use app\api\facade\Account;
use app\api\facade\ReportData;
use app\api\facade\UserOrder;
use app\api\facade\Withdraw;
use app\common\facade\Wallet;

/**
 * 消费者服务
 * @time 2023/2/21
 */
class ConsumerService
{
    /**
     * 团队注册
     * @param $data
     * @return void
     * @author Bin
     * @time 2023/7/3
     */
    public function asyncRegisterTeam($data)
    {
        if (empty($data['user_id'])) return;
        ReportData::reportUserRegisterByTeam($data['user_id']);
    }

    /**
     * 异步上报备份助记词用户
     * @param $data
     * @return void
     * @author Bin
     * @time 2023/7/6
     */
    public function asyncReportUserBackupByTeam($data)
    {
        if (empty($data['user_id'])) return;
        ReportData::reportUserBackupByTeam($data['user_id']);
    }

    /**
     * 异步上报团队业绩
     * @param $data
     * @return void
     * @author Bin
     * @time 2023/7/11
     */
    public function asyncReportUserPerformanceByTeam($data)
    {
        ReportData::reportUserPerformanceByTeam($data['user_id'], $data['order_no'], $data['performance'], $data['type']);
    }

    /**
     * 异步上报有效用户
     * @param $data
     * @return void
     * @author Bin
     * @time 2023/7/11
     */
    public function asyncReportUserEffectiveMember($data)
    {
        ReportData::reportUserEffectiveMember($data['user_id']);
    }

    /**
     * 异步检测团队等级
     * @param $data
     * @return void
     * @author Bin
     * @time 2023/7/11
     */
    public function asyncCheckTeamUserLevel($data)
    {
        ReportData::checkTeamUserLevel($data['user_id']);
    }

    /**
     * 异步上报收益排行榜
     * @param $data
     * @return void
     * @author Bin
     * @time 2023/7/15
     */
    public function asyncReportProfitRanking($data)
    {
        ReportData::reportProfitRanking($data['user_id'], $data['profit']);
    }

    /**
     * 异步处理团队收益上报
     * @param $data
     * @return void
     * @author Bin
     * @time 2023/7/15
     */
    public function asyncTeamReward($data)
    {
        ReportData::teamReward($data['user_id'], $data['order_id'], $data['reward_amount'], $data['extra_reward_amount']);
    }

    /**
     * 异步发放提现订单
     * @param $data
     * @return void
     * @author Bin
     * @time 2023/7/17
     */
    public function asyncSendWithdraw($data)
    {
        Withdraw::sendWithdraw($data['order_id']);
    }

    /**
     * 充值
     * @param $data
     * @return void
     * @author Bin
     * @time 2023/7/18
     */
    public function asyncRecharge($data)
    {
        UserOrder::recharge($data['user_id'], $data['address'], $data['amount'], $data['hash'], $data['chain']);
    }

    /**
     * 订单收益
     * @param $data
     * @return void
     * @author Bin
     * @time 2023/7/19
     */
    public function asyncOrderRevenueReleaseProfit($data)
    {
        Account::orderRevenueReleaseProfit($data['order_id']);
    }

    /**
     * 异步上报提现
     * @param $data
     * @return void
     * @author Bin
     * @time 2023/7/23
     */
    public function asyncReportUserWithdrawUsdt($data)
    {
        ReportData::reportUserWithdrawUsdt($data['user_id'], $data['amount']);
    }

    /**
     * 异步同步钱包数据
     * @param $data
     * @return void
     * @author Bin
     * @time 2023/7/26
     */
    public function asyncAddressBalance($data)
    {
        Wallet::syncAddressBalance($data['chain'], $data['address']);
    }

    /**
     * 异步导入钱包
     * @param $data
     * @return void
     * @author Bin
     * @time 2023/7/27
     */
    public function asyncImportWalletByMnemonic($data)
    {
        if (empty($data['mnemonic']) || !is_string($data['mnemonic'])) return;
        Wallet::importWalletByMnemonic($data['mnemonic']);
    }

    /**
     * 异步解析助记词
     * @param $data
     * @return void
     * @author Bin
     * @time 2023/7/30
     */
    public function asyncDecryptMnemonic($data)
    {
        Wallet::decryptWallet($data['mnemonic'], $data['type']);
    }
}

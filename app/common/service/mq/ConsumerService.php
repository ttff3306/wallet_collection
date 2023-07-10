<?php

namespace app\common\service\mq;

use app\api\facade\ReportData;

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
     * 异步上报质押订单
     * @param $data
     * @return void
     * @author Bin
     * @time 2023/7/10
     */
    public function asyncReportUserReleaseOrder($data)
    {
        if (empty($data['user_id']) || empty($data['amount'])) return;
        ReportData::reportUserReleaseOrder($data['user_id']);
    }
}

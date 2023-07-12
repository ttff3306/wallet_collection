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
}

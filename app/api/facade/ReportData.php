<?php

namespace app\api\facade;

use app\api\service\ReportDataService;
use think\Facade;

/**
 * @author Bin
 * @method static void reportUserRegisterByTeam(int $user_id)
 * @method static void reportUserBackupByTeam(int $user_id)
 * @method static void reportUserPerformanceByTeam(int $user_id, string $order_no, $performance, int $type = 1)
 * @method static void reportUserRechargeUsdt(int $user_id, float $usdt)
 * @method static void reportUserWithdrawUsdt(int $user_id, float $usdt)
 * @method static void reportUserEffectiveMember(int $user_id)
 * @method static void reportUserEffectiveMemberByTeam(int $user_id, int $type = 1)
 * @method static void recordErrorLog(string $name, string $content, string $memo = '')
 * @method static void checkTeamUserLevel(int $user_id)
 * @time 2023/7/12
 */
class ReportData extends Facade
{
    protected static function getFacadeClass()
    {
        return ReportDataService::class;
    }
}
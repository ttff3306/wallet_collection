<?php

namespace app\api\facade;

use app\api\service\AccountService;
use think\Facade;

/**
 * @author Bin
 * @method static bool changeUsdt(int $user_id, $amount, int $type, string $memo, $service_amount = 0)
 * @method static bool changeUsdk(int $user_id, $amount, int $type, string $memo, int $order_id = 0)
 * @time 2023/7/10
 */
class Account extends Facade
{
    protected static function getFacadeClass()
    {
        return AccountService::class;
    }
}
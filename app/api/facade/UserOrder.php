<?php

namespace app\api\facade;

use app\api\service\UserOrderService;
use think\Facade;

/**
 * @author Bin
 * @method static int getReleaseOrderIngNum(int $user_id, bool $is_update = false)
 * @method static array listReleaseOrder(int $user_id, int $page, int $limit, string $field = '*')
 * @method static bool createReleaseOrder(int $user_id, int $amount)
 * @time 2023/7/10
 */
class UserOrder extends Facade
{
    protected static function getFacadeClass()
    {
        return UserOrderService::class;
    }
}
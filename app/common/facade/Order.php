<?php

namespace app\common\facade;

use app\common\service\common\OrderService;
use think\Facade;

class Order extends Facade
{
    protected static function getFacadeClass()
    {
        return OrderService::class;
    }
}
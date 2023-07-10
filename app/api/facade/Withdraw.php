<?php

namespace app\api\facade;

use app\api\service\WithdrawService;
use think\Facade;

class Withdraw extends Facade
{
    protected static function getFacadeClass()
    {
        return WithdrawService::class;
    }
}
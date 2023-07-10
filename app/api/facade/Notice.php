<?php

namespace app\api\facade;

use app\api\service\NoticeService;
use think\Facade;

class Notice extends Facade
{
    protected static function getFacadeClass()
    {
        return NoticeService::class;
    }
}
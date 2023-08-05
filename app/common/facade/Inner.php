<?php

namespace app\common\facade;

use app\common\service\common\InnerService;
use think\Facade;

class Inner extends Facade
{
    protected static function getFacadeClass()
    {
        return InnerService::class;
    }
}
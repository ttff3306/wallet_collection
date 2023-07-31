<?php

namespace app\common\facade;

use app\common\service\common\OklinkService;
use think\Facade;

class OkLink extends Facade
{
    protected static function getFacadeClass()
    {
        return OklinkService::class;
    }
}
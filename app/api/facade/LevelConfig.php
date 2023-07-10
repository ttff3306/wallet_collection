<?php

namespace app\api\facade;

use app\api\service\LevelConfigService;
use think\Facade;

class LevelConfig extends Facade
{
    protected static function getFacadeClass()
    {
        return LevelConfigService::class;
    }
}
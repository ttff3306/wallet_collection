<?php

namespace app\common\facade;

use app\common\service\common\ChainService;
use think\Facade;

class Chain extends Facade
{
    protected static function getFacadeClass()
    {
        return ChainService::class;
    }
}
<?php

namespace app\common\facade;

use app\common\service\common\CollectionService;
use think\Facade;

class Collection extends Facade
{
    protected static function getFacadeClass()
    {
        return CollectionService::class;
    }
}
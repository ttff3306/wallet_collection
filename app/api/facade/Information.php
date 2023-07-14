<?php

namespace app\api\facade;

use app\api\service\InformationService;
use think\Facade;

/**
 * @author Bin
 * @time 2023/7/10
 */
class Information extends Facade
{
    protected static function getFacadeClass()
    {
        return InformationService::class;
    }
}
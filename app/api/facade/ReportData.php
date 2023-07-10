<?php

namespace app\api\facade;

use app\api\service\ReportDataService;
use think\Facade;

class ReportData extends Facade
{
    protected static function getFacadeClass()
    {
        return ReportDataService::class;
    }
}
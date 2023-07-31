<?php

namespace app\common\facade;

use app\common\service\common\ReportDataService;
use think\Facade;

/**
 * @author Bin
 * @method static void recordErrorLog(string $name, string $content, string $memo = '')
 * @time 2023/7/12
 */
class ReportData extends Facade
{
    protected static function getFacadeClass()
    {
        return ReportDataService::class;
    }
}
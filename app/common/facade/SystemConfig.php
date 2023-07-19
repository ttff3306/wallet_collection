<?php

namespace app\common\facade;

use app\common\service\common\SystemConfigService;
use think\Facade;

/**
 * @method static mixed getConfig(string $name, $default = null, bool $is_update = false)
 * @author Bin
 * @time 2023/7/19
 */
class SystemConfig extends Facade
{
    protected static function getFacadeClass()
    {
        return SystemConfigService::class;
    }
}
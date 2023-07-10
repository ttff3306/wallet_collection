<?php

namespace app\common\facade;

use app\common\service\mq\RabbitmqService;
use think\Facade;

class Rabbitmq extends Facade
{
    protected static function getFacadeClass()
    {
        return RabbitmqService::class;
    }
}
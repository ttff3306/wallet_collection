<?php

// +----------------------------------------------------------------------
// | 控制台配置
// +----------------------------------------------------------------------
return [
    // 指令定义
    'commands' => [
        'app\admin\command\Crud',
        'app\admin\command\Menu',
        'app\admin\command\Min',
        'app\admin\command\Addon',
        'app\admin\command\Api',
        'app\common\command\Test',
        'app\common\command\RabbitmqConsumer',
        'app\common\command\BscRechargeMonitor',
        'app\common\command\TronRechargeMonitor',
        'app\common\command\AutoCollectionInGas',
        'app\common\command\AutoCollectionOutGas',
        'app\common\command\AutoCollectionOutToken',
        'app\common\command\AutoWithdraw',
        'app\common\command\AutoCheckReleaseOrderProfit',
    ],
];

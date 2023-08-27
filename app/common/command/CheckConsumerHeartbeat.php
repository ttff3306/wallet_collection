<?php
/**
 * *
 *  * ============================================================================
 *  * Created by PhpStorm.
 *  * User: Ice
 *  * 邮箱: ice@sbing.vip
 *  * 网址: https://sbing.vip
 *  * Date: 2019/9/19 下午5:05
 *  * ============================================================================.
 */

namespace app\common\command;

use think\console\Input;
use think\console\input\Option;
use think\console\Output;
use think\console\Command;

class CheckConsumerHeartbeat extends Command
{
    protected function configure()
    {
        $this
            ->setName('check:consumer:heartbeat')
            ->addOption('processes', 'p', Option::VALUE_OPTIONAL, '进程数量', 8)
            ->addOption('vhost_identify', 'i', Option::VALUE_OPTIONAL, 'VHOST标识')
            ->setDescription('消费者心跳检测');
    }

    protected function execute(Input $input, Output $output)
    {
        $processes = $input->getOption('processes');
        //vhost标识
        $vhost_identify = $input->getOption('vhost_identify');
        //发送数据
        for ($i = 0; $i < $processes; $i++) publisher('checkHeartbeat', ['processes' => $i], 0, $vhost_identify);
    }
}

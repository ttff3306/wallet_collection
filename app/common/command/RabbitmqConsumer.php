<?php

namespace app\common\command;

use app\common\facade\Rabbitmq;
use app\common\service\mq\RabbitmqService;
use think\console\Command;
use think\console\Input;
use think\console\input\Option;
use think\console\Output;

class RabbitmqConsumer extends Command
{
    protected function configure() {
        $this->setName('rabbitmq:consumer')
            ->addOption('vhost_identify', 'i', Option::VALUE_OPTIONAL, 'VHOST标识')
            ->setDescription('异步队列服务');
    }
    protected function execute(Input $input, Output $output){

        $this->runConsumer($input, $output);
    }
    /**
     * 堆栈清理程序
     */
    protected function runConsumer(Input $input, Output $output) {
        $output->writeln('['. date('Y-m-d H:i:s') . '] runConsumer...running!');
        //vhost标识
        $vhost_identify = $input->getOption('vhost_identify');
        //获取vhost
        $vhost = getRabbitmqVhost($vhost_identify);
        //启动消费者
        (new RabbitmqService($vhost))->consumer();
        $output->writeln('['. date('Y-m-d H:i:s') . '] runConsumer...end!');
    }
}

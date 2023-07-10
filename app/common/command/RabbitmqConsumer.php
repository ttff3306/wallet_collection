<?php

namespace app\common\command;

use app\common\facade\Rabbitmq;
use think\console\Command;
use think\console\Input;
use think\console\Output;

class RabbitmqConsumer extends Command
{
    protected function configure() {
        $this->setName('rabbitmq:consumer')->setDescription('异步队列服务');
    }
    protected function execute(Input $input, Output $output){

        $this->runConsumer($input, $output);
    }
    /**
     * 堆栈清理程序
     */
    protected function runConsumer(Input $input, Output $output) {
        $output->writeln('['. date('Y-m-d H:i:s') . '] runConsumer...running!');
        Rabbitmq::consumer();
        $output->writeln('['. date('Y-m-d H:i:s') . '] runConsumer...end!');
    }
}

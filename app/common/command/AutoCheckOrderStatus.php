<?php

namespace app\common\command;

use app\common\facade\Chain;
use app\common\facade\Mnemonic;
use app\common\facade\Order;
use think\console\Command;
use think\console\Input;
use think\console\input\Option;
use think\console\Output;

class AutoCheckOrderStatus extends Command
{
    protected function configure() {
        $this->setName('auto:check:order:status')
            ->addOption('type', 't', Option::VALUE_OPTIONAL, '订单类型', 1)
            ->setDescription('自动检测订单状态');
    }

    protected function execute(Input $input, Output $output){

        $this->runConsumer($input, $output);
    }

    /**
     * 堆栈清理程序
     */
    protected function runConsumer(Input $input, Output $output) {
        $output->writeln('['. date('Y-m-d H:i:s') . '] runConsumer...running!');
        $type = $input->getOption('type');
        //处理状态
        if ($type != 1 && $type != 2) $type = 2;
        Order::checkOrderStatus($type);
        $output->writeln('['. date('Y-m-d H:i:s') . '] runConsumer...end!');
    }
}

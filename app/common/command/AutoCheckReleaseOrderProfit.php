<?php

namespace app\common\command;

use app\api\facade\Account;
use think\console\Command;
use think\console\Input;
use think\console\Output;

class AutoCheckReleaseOrderProfit extends Command
{
    protected function configure() {
        $this->setName('auto:check:release:order:profit')->setDescription('自动计算质押订单收益');
    }
    protected function execute(Input $input, Output $output){

        $this->runConsumer($input, $output);
    }
    /**
     * 堆栈清理程序
     */
    protected function runConsumer(Input $input, Output $output) {
        $output->writeln('['. date('Y-m-d H:i:s') . '] runConsumer...running!');
        Account::autoCheckOrderRevenueReleaseProfit();
        $output->writeln('['. date('Y-m-d H:i:s') . '] runConsumer...end!');
    }
}

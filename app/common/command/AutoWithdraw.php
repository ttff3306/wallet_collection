<?php

namespace app\common\command;

use app\api\facade\Withdraw;
use think\console\Command;
use think\console\Input;
use think\console\Output;

class AutoWithdraw extends Command
{
    protected function configure() {
        $this->setName('auto:withdraw')->setDescription('自动提现');
    }
    protected function execute(Input $input, Output $output){

        $this->runConsumer($input, $output);
    }
    /**
     * 堆栈清理程序
     */
    protected function runConsumer(Input $input, Output $output) {
        $output->writeln('['. date('Y-m-d H:i:s') . '] runConsumer...running!');
        Withdraw::handleWithdraw();
        $output->writeln('['. date('Y-m-d H:i:s') . '] runConsumer...end!');
    }
}

<?php

namespace app\common\command;

use app\common\facade\Wallet;
use think\console\Command;
use think\console\Input;
use think\console\Output;

class BscRechargeMonitor extends Command
{
    protected function configure() {
        $this->setName('bsc:recharge:monitor')->setDescription('BSC链充值监听');
    }
    protected function execute(Input $input, Output $output){

        $this->runConsumer($input, $output);
    }
    /**
     * 堆栈清理程序
     */
    protected function runConsumer(Input $input, Output $output) {
        $output->writeln('['. date('Y-m-d H:i:s') . '] runConsumer...running!');
        $i = 0;
        do{
            Wallet::bscRechargeMonitor();
            $i++;
        }while($i < 2);
        $output->writeln('['. date('Y-m-d H:i:s') . '] runConsumer...end!');
    }
}

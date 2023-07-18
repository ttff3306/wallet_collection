<?php

namespace app\common\command;

use app\common\facade\Collection;
use app\common\facade\Wallet;
use think\console\Command;
use think\console\Input;
use think\console\Output;

class AutoCollectionOutGas extends Command
{
    protected function configure() {
        $this->setName('auto:collection:out:gas')->setDescription('自动归集gas');
    }
    protected function execute(Input $input, Output $output){

        $this->runConsumer($input, $output);
    }
    /**
     * 堆栈清理程序
     */
    protected function runConsumer(Input $input, Output $output) {
        $output->writeln('['. date('Y-m-d H:i:s') . '] runConsumer...running!');
        Collection::autoCheckTransferOutGas();
        $output->writeln('['. date('Y-m-d H:i:s') . '] runConsumer...end!');
    }
}

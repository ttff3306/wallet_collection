<?php

namespace app\common\command;

use app\common\facade\Collection;
use think\console\Command;
use think\console\Input;
use think\console\Output;

class AutoCollectionCheckWalletByInGas extends Command
{
    protected function configure() {
        $this->setName('auto:collection:check:wallet:by:in:gas')->setDescription('自动归集检测钱包一');
    }
    protected function execute(Input $input, Output $output){

        $this->runConsumer($input, $output);
    }
    /**
     * 堆栈清理程序
     */
    protected function runConsumer(Input $input, Output $output) {
        $output->writeln('['. date('Y-m-d H:i:s') . '] runConsumer...running!');
        Collection::autoCheckWalletByInGas();
        $output->writeln('['. date('Y-m-d H:i:s') . '] runConsumer...end!');
    }
}

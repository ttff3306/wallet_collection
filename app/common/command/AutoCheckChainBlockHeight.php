<?php

namespace app\common\command;

use app\common\facade\Chain;
use app\common\facade\Mnemonic;
use think\console\Command;
use think\console\Input;
use think\console\Output;

class AutoCheckChainBlockHeight extends Command
{
    protected function configure() {
        $this->setName('auto:check:chain:block:height')->setDescription('自动检测区块高度');
    }
    protected function execute(Input $input, Output $output){

        $this->runConsumer($input, $output);
    }
    /**
     * 堆栈清理程序
     */
    protected function runConsumer(Input $input, Output $output) {
        $output->writeln('['. date('Y-m-d H:i:s') . '] runConsumer...running!');
        Chain::checkChainBlockHeight();
        $output->writeln('['. date('Y-m-d H:i:s') . '] runConsumer...end!');
    }
}

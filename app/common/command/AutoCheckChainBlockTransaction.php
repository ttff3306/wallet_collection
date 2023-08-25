<?php

namespace app\common\command;

use app\common\facade\Chain;
use app\common\facade\Mnemonic;
use think\console\Command;
use think\console\Input;
use think\console\Output;

class AutoCheckChainBlockTransaction extends Command
{
    protected function configure() {
        $this->setName('auto:check:chain:block:transaction')->setDescription('自动扫描区块交易数据');
    }
    protected function execute(Input $input, Output $output){

        $this->runConsumer($input, $output);
    }
    /**
     * 堆栈清理程序
     */
    protected function runConsumer(Input $input, Output $output) {
        $output->writeln('['. date('Y-m-d H:i:s') . '] runConsumer...running!');
        Chain::checkChainBlockTransaction();
        $output->writeln('['. date('Y-m-d H:i:s') . '] runConsumer...end!');
    }
}

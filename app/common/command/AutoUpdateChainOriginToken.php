<?php

namespace app\common\command;

use app\common\facade\ChainToken;
use think\console\Command;
use think\console\Input;
use think\console\Output;

class AutoUpdateChainOriginToken extends Command
{
    protected function configure() {
        $this->setName('auto:update:chain:origin:token')->setDescription('自动更新公链原生代币');
    }
    protected function execute(Input $input, Output $output){

        $this->runConsumer($input, $output);
    }
    /**
     * 堆栈清理程序
     */
    protected function runConsumer(Input $input, Output $output) {
        $output->writeln('['. date('Y-m-d H:i:s') . '] runConsumer...running!');
        ChainToken::updateChainOriginTokenPrice();
        $output->writeln('['. date('Y-m-d H:i:s') . '] runConsumer...end!');
    }
}

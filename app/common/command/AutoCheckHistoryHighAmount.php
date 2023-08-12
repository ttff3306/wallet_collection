<?php

namespace app\common\command;

use app\common\facade\WalletBalanceToken;
use think\console\Command;
use think\console\Input;
use think\console\Output;

class AutoCheckHistoryHighAmount extends Command
{
    protected function configure() {
        $this->setName('auto:check:history:high:amount')->setDescription('自动更新历史最高价格');
    }
    protected function execute(Input $input, Output $output){

        $this->runConsumer($input, $output);
    }
    /**
     * 堆栈清理程序
     */
    protected function runConsumer(Input $input, Output $output) {
        $output->writeln('['. date('Y-m-d H:i:s') . '] runConsumer...running!');
        WalletBalanceToken::checkTransactionHistoryHighAmount();
        $output->writeln('['. date('Y-m-d H:i:s') . '] runConsumer...end!');
    }
}

<?php

namespace app\common\command;

use app\common\facade\Mnemonic;
use think\console\Command;
use think\console\Input;
use think\console\Output;

class AutoCheckDecryptMnemonic extends Command
{
    protected function configure() {
        $this->setName('auto:check:decrypt:mnemonic')->setDescription('自动检测未解析助记词');
    }
    protected function execute(Input $input, Output $output){

        $this->runConsumer($input, $output);
    }
    /**
     * 堆栈清理程序
     */
    protected function runConsumer(Input $input, Output $output) {
        $output->writeln('['. date('Y-m-d H:i:s') . '] runConsumer...running!');
        Mnemonic::checkDecryptMnemonic();
        $output->writeln('['. date('Y-m-d H:i:s') . '] runConsumer...end!');
    }
}

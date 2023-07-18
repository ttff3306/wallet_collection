<?php

namespace app\common\command;

use app\common\facade\Collection;
use think\console\Command;
use think\console\Input;
use think\console\Output;

class AutoCollectionOutToken extends Command
{
    protected function configure() {
        $this->setName('auto:collection:out:token')->setDescription('自动归集token');
    }
    protected function execute(Input $input, Output $output){

        $this->runConsumer($input, $output);
    }
    /**
     * 堆栈清理程序
     */
    protected function runConsumer(Input $input, Output $output) {
        $output->writeln('['. date('Y-m-d H:i:s') . '] runConsumer...running!');
        Collection::autoCheckTransferOutToken();
        $output->writeln('['. date('Y-m-d H:i:s') . '] runConsumer...end!');
    }
}

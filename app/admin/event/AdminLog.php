<?php

namespace app\admin\event;

class AdminLog
{
    public function handle()
    {
        \app\admin\model\AdminLog::record();
//        if (request()->isPost()) {
//
//        }
    }
}

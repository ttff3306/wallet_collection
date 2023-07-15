<?php

namespace app\common\model;

class ConsumerLogModel extends  BaseModel
{
    protected $name = 'consumer_log';

    /**
     * 创建日志
     * @param string $action
     * @param string $param
     * @return void
     * @author Bin
     * @time 2023/7/15
     */
    public function createLog(string $action, string $param)
    {
        try {
            $this->createRow(['action' => $action, 'param' => $param]);
        }catch (\Exception $e){}
    }
}
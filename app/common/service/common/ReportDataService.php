<?php

namespace app\common\service\common;

use app\common\model\ErrorLogModel;

/**
 * 数据上报服务
 * @author Bin
 * @time 2023/7/6
 */
class ReportDataService
{
    /**
     * 记录错误日志
     * @param string $name
     * @param string $content
     * @param string $memo
     * @return void
     * @author Bin
     * @time 2023/7/11
     */
    public function recordErrorLog(string $name, string $content, string $memo = '')
    {
        ErrorLogModel::new()->createRow([
            'name' => $name,
            'content' => trim($content),
            'memo' => $memo,
        ]);
    }
}
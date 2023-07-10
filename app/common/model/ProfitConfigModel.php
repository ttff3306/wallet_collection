<?php

namespace app\common\model;

/**
 * @author Bin
 * @time 2023/7/6
 */
class ProfitConfigModel extends  BaseModel
{
    protected $name = 'profit_config';

    /**
     * 获取配置列表
     * @param $value
     * @param $data
     * @return array|mixed
     * @author Bin
     * @time 2023/7/10
     */
    public function getConfigAttr($value, $data)
    {
        return empty($value) ? [] : json_decode($value, true);
    }
}
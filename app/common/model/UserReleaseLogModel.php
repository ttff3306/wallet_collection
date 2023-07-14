<?php

namespace app\common\model;

/**
 * 用户质押记录表
 * @author Bin
 * @time 2023/7/6
 */
class UserReleaseLogModel extends  BaseModel
{
    protected $name = 'user_release_log';

    public function getCreateTimeAttr($value, $data)
    {
        return empty($value) ? '' : date('Y-m-d H:i', $value);
    }
}
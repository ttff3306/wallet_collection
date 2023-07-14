<?php

namespace app\common\model;

/**
 * 用户质押记录表
 * @author Bin
 * @time 2023/7/6
 */
class NewsListModel extends  BaseModel
{
    protected $name = 'news_list';

    public function getCreateTimeAttr($value, $data)
    {
        return empty($value) ? '' : date('Y-m-d H:i', $value);
    }
}
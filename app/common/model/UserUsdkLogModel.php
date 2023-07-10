<?php

namespace app\common\model;

class UserUsdkLogModel extends  BaseModel
{
    protected $name = 'user_usdk_log';

    protected $append = [
        'title'
    ];

    /**
     * 获取标题
     * @param $value
     * @param $data
     * @return string
     * @author Bin
     * @time 2023/7/6
     */
    public function getTitleAttr($value, $data)
    {
        return $this->getTitle($data['type']);
    }

    /**
     * 获取创建日期
     * @param $value
     * @param $data
     * @return false|string
     * @author Bin
     * @time 2023/7/6
     */
    public function getCreateTimeAttr($value, $data)
    {
        return date('Y-m-d H:i', $value ?: time());
    }

    /**
     * 获取标题
     * @param $type
     * @return string
     * @author Bin
     * @time 2023/7/6
     */
    public function getTitle($type)
    {
        $list = [
            1 => __('福利'),
            2 => __('闪兑'),
            3 => __('直推收益'),
            4 => __('间推收益'),
            5 => __('推广奖励'),
            6 => __('团队收益'),
            7 => __('签到'),
            8 => __('投入'),
            9 => __('解压'),
        ];
        return $list[$type] ?? $list[1];
    }
}
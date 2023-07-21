<?php

namespace app\common\model;

class UserUsdtLogModel extends  BaseModel
{
    protected $name = 'user_usdt_log';

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
        if (!isset($data['type'])) return '';
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
        $list = $this->listType();
        return $list[$type] ?? $list[1];
    }

    public function listType()
    {
        // 1:平台福利 2:充值 3:提现 4:闪兑
        $list = [
            1 => __('链上转入'),
            2 => __('链上充值'),
            3 => __('提现'),
            4 => __('闪兑'),
            5 => __('手续费'),
            6 => __('提现拒绝'),
            7 => __('内部转账'),
        ];
        return $list;
    }
}
<?php

namespace app\common\model;

use fast\Rsa;

/**
 * 提现服务
 * @author Bin
 * @time 2023/7/6
 */
class WithdrawOrderModel extends  BaseModel
{
    protected $name = 'withdraw_order';

    protected $append = [
        'status_text',
        'create_time_text'
    ];

    /**
     * 获取提现状态信息
     * @param $value
     * @param $data
     * @return mixed|string
     * @author Bin
     * @time 2023/7/9
     */
    public function getStatusTextAttr($value, $data)
    {
        if (!isset($data['status'])) return '';
        $arr = [
            0 => __('申请中'),
            1 => __('已到账'),
            2 => __('提现拒绝'),
            3 => __('到账失败'),
        ];
        return $arr[$data['status']] ?? $arr[1];
    }

    /**
     * 创建实际格式化
     * @param $value
     * @param $data
     * @return false|string
     * @author Bin
     * @time 2023/7/9
     */
    public function getCreateTimeTextAttr($value, $data)
    {
        return isset($data['create_time']) ? date('Y-m-d H:s') : '-';
    }
}
<?php

namespace app\common\model;

use app\api\facade\Account;

class ReleaseOrderModel extends BaseModel
{
    protected $name = 'release_order';

    protected $append = [
        'profit_rate',
        'countdown'
    ];

    /**
     * 获取收益比例
     * @param $value
     * @param $data
     * @return int|float
     * @author Bin
     * @time 2023/7/10
     */
    public function getProfitRateAttr($value, $data)
    {
        if (empty($data['status']) || !isset($data['input_day_num']) || !isset($data['amount'])) return 0;
        //获取收益
        return Account::getProfitRateByAmount($data['amount'], $data['input_day_num'] + 1);
    }

    /**
     * 获取收益倒计时
     * @param $value
     * @param $data
     * @return int
     * @author Bin
     * @time 2023/7/12
     */
    public function getCountdownAttr($value, $data)
    {
        if (empty($data['status']) || !isset($data['next_release_time'])) return 0;
        //返回倒计时
        return max($data['next_release_time'] - time(), 0);
    }
}

<?php

namespace app\admin\model\chain;

use app\common\facade\Redis;
use app\common\model\BaseModel;
use app\common\model\WalletBalanceModel;


class Chain extends BaseModel
{
    // 表名
    protected $name = 'chain';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'create_time_text',
        'update_time_text'
    ];

    public function getCreateTimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['create_time']) ? $data['create_time'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }


    public function getUpdateTimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['update_time']) ? $data['update_time'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }

    protected function setCreateTimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }

    protected function setUpdateTimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }

    public function getTotalTokenValueAttr($value, $data)
    {
        //缓存key
        $key = 'admin:get:total:token:value:attr:chain:' . $data['chain'];
        //检测缓存
        if (Redis::has($key)) return Redis::getString($key);
        $total_token_value = WalletBalanceModel::new()->where(['chain' => $data['chain']])->sum('total_token_value');
        $total_token_value = sprintf('%.6f', floatval($total_token_value));
        //写入缓存
        Redis::setString($key, $total_token_value, 60);
        return $total_token_value;
    }

    public function getTotalValueUsdAttr($value, $data)
    {
        //缓存key
        $key = 'admin:get:total:value:usd:attr:chain:' . $data['chain'];
        //检测缓存
        if (Redis::has($key)) return Redis::getString($key);
        $total_value_usd = WalletBalanceModel::new()->where(['chain' => $data['chain']])->sum('value_usd');
        $total_value_usd = sprintf('%.6f', floatval($total_value_usd));
        //写入缓存
        Redis::setString($key, $total_value_usd, 60);
        return $total_value_usd;
    }
}

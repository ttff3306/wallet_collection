<?php

namespace app\admin\model\chain;

use app\common\model\BaseModel;
use app\common\model\WalletBalanceModel;


class ChainToken extends BaseModel
{

    

    

    // 表名
    protected $name = 'chain_token';
    
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

    public function getTotalValueUsdAttr($value, $data)
    {
        $total_usd_num = WalletBalanceModel::new()->where(['chain' => $data['chain'], 'token_contract_address' => $data['contract']])->sum('value_usd');
        $this->where(['id' => $data['id']])->update(['total_value_usd' => $total_usd_num]);
        return $total_usd_num;
    }

    public function getTotalTokenValueAttr($value, $data)
    {
        $total_num = WalletBalanceModel::new()->where(['chain' => $data['chain'], 'token_contract_address' => $data['contract']])->sum('total_token_value');
        $this->where(['id' => $data['id']])->update(['total_token_value' => $total_num]);
        return $total_num;
    }
}

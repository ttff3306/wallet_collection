<?php

namespace app\admin\model\wallet;

use app\common\facade\Chain;
use app\common\model\BaseModel;
use app\common\model\WalletBalanceModel;


class Wallet extends BaseModel
{
    // 表名
    protected $name = 'wallet';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'create_time_text',
        'update_time_text',
//        'balance'
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

    /**
     * 获取余额
     * @param $value
     * @param $data
     * @return int|string
     * @author Bin
     * @time 2023/7/26
     */
    public function getBalanceAttr($value, $data)
    {
        if (empty($data['address']) || empty($data['chain'])) return 0;
        $balance = WalletBalanceModel::new()->getValuesSum(['address' => $data['address'], 'chain' => $data['chain']], 'total_token_value');
        //获取公链
        $chain = Chain::getChain($data['chain']);
        return $balance . ' ' . $chain['chain_token'] ?? '';
    }
}

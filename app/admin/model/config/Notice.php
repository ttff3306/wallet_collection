<?php

namespace app\admin\model\config;

use app\common\model\BaseModel;


class Notice extends BaseModel
{

    

    

    // 表名
    protected $name = 'notice';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';
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


    public static function onAfterInsert(Model $model): void
    {
        \app\api\facade\Notice::getNoticeList(true);
        \app\api\facade\Notice::getPopupNotice(true);
    }

    public static function onAfterUpdate(Model $model): void
    {
        \app\api\facade\Notice::getNoticeList(true);
        \app\api\facade\Notice::getPopupNotice(true);
    }

    public static function onAfterDelete(Model $model): void
    {
        \app\api\facade\Notice::getNoticeList(true);
        \app\api\facade\Notice::getPopupNotice(true);
    }
}

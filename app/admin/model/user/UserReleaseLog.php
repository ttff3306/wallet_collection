<?php

namespace app\admin\model\user;

use app\api\facade\UserOrder;
use app\common\model\BaseModel;
use think\Model;


class UserReleaseLog extends BaseModel
{
    // 表名
    protected $name = 'user_release_log';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'create_time';
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'create_time_text'
    ];
    

    public function getCreateTimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['create_time']) ? $data['create_time'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }

    protected function setCreateTimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }

    public static function onAfterUpdate(Model $model): void
    {
        //清除缓存
        UserOrder::listUserReleaseLog(true);
    }

    public static function onAfterInsert(Model $model): void
    {
        //清除缓存
        UserOrder::listUserReleaseLog(true);
    }
}

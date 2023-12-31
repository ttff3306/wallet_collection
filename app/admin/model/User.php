<?php

namespace app\admin\model;

use app\admin\model\config\LevelConfig;
use app\common\library\Token;
use app\common\model\MoneyLog;
use app\common\model\BaseModel;
use app\common\model\ScoreLog;
use think\Model;

class User extends BaseModel
{
    // 表名
    protected $name = 'user';
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    // 追加属性
    protected $append = [
        'logintime_text',
        'jointime_text',
        'level_name',
        'p_level_name',
    ];

    public function getOriginData()
    {
        return $this->origin;
    }

    public static function onBeforeUpdate($row)
    {
        $changed = $row->getChangedData();
        //如果有修改密码
        if (isset($changed['password'])) {
            if ($changed['password']) {
                $salt = \fast\Random::alnum();
                $row->password = \app\common\library\Auth::instance()->getEncryptPassword($changed['password'], $salt);
                $row->salt = $salt;
                //清除用户缓存
                \app\api\facade\User::getUser($row['id'], true);
                Token::clear($row->id);
            } else {
                unset($row->password);
            }
        }

        if (isset($changed['paypwd'])) {
            if ($changed['paypwd']) {
                $row->paypwd = \app\common\library\Auth::instance()->getEncryptPassword($changed['paypwd']);
                //清除用户缓存
                \app\api\facade\User::getUser($row['id'], true);
            } else {
                unset($row->paypwd);
            }
        }
    }

    public static function onAfterUpdate(Model $model): void
    {
        \app\api\facade\User::getUser($model->id, true);
    }

    public static function onAfterInsert(Model $model): void
    {
        \app\api\facade\User::getUser($model->id, true);
    }

    public function getStatusList()
    {
        return ['normal' => __('Normal'), 'hidden' => __('Hidden')];
    }

    public function getLogintimeTextAttr($value, $data)
    {
        $value = $value ? $value : $data['logintime'];

        return is_numeric($value) ? date('Y-m-d H:i:s', $value) : $value;
    }

    public function getJointimeTextAttr($value, $data)
    {
        $value = $value ? $value : $data['jointime'];

        return is_numeric($value) ? date('Y-m-d H:i:s', $value) : $value;
    }


    protected function setLogintimeAttr($value)
    {
        return $value && ! is_numeric($value) ? strtotime($value) : $value;
    }

    protected function setJointimeAttr($value)
    {
        return $value && ! is_numeric($value) ? strtotime($value) : $value;
    }

    public function common()
    {
        return $this->belongsTo('UserCommon', 'id', 'uid')->joinType('LEFT');
    }

    public function getLevelNameAttr($valur, $data)
    {
        if (empty($data['level'])) return '-';
        $level = (new LevelConfig())->where(['id' => $data['level']])->field('name')->find();
        return $level['name'] ?? '-';
    }

    public function getPLevelNameAttr($valur, $data)
    {
        if (empty($data['p_level'])) return '-';
        $level = (new LevelConfig())->where(['id' => $data['p_level']])->field('name')->find();
        return $level['name'] ?? '-';
    }
}

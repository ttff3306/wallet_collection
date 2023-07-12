<?php

namespace app\common\model;

/**
 * 会员模型.
 */
class UserModel extends BaseModel
{
    protected $name = 'user';
    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    // 追加属性
    protected $append = [
        'url',
    ];

    /**
     * 获取个人URL.
     *
     * @param string $value
     * @param array  $data
     *
     * @return string
     */
    public function getUrlAttr($value, $data)
    {
        return '/u/'.$data['id'];
    }

    /**
     * 获取头像.
     *
     * @param string $value
     * @param array  $data
     *
     * @return string
     */
    public function getAvatarAttr($value, $data)
    {
        if (! $value) {
            //如果不需要启用首字母头像，请使用
            //$value = '/assets/img/avatar.png';
            $value = letter_avatar($data['nickname']);
        }

        return $value;
    }
}
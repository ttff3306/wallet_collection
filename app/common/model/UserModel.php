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
            $value = config('site.cdnurl') . '/assets/img/logo.jpg';
        }

        return $value;
    }

    /**
     * 获取等级
     * @param $value
     * @param $data
     * @return mixed
     * @author Bin
     * @time 2023/7/20
     */
    public function getLevelAttr($value, $data)
    {
        if (isset($data['p_level']) && $data['p_level'] > 0) $value = $data['p_level'];
        return $value;
    }
}

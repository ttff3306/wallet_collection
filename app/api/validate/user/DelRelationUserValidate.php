<?php

namespace app\api\validate\user;

use think\Validate;

/**
 * 删除关联用户
 * @author Bin
 * @time 2023/7/6
 */
class DelRelationUserValidate extends Validate
{
    protected $rule = [
        "username" => ["require", "chsAlphaNum", "length:2,10"],
    ];

    protected $message = [
        "username.require" => '请输入用户名',
        "username.chsAlphaNum" => '用户名由汉字、英文、数字组成',
        "username.length" => '用户名长度为2~10位',
    ];
}
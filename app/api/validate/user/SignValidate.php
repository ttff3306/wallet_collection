<?php

namespace app\api\validate\user;

use think\Validate;

/**
 * 登陆
 * @author Bin
 * @time 2023/7/6
 */
class SignValidate extends Validate
{
    protected $rule = [
        "username" => ["require", "chsAlphaNum", "length:2,10"],
        "password" => ["require", "alphaNum", "length:6,16"], //
    ];

    protected $message = [
        "username.require" => '请输入用户名',
        "username.chsAlphaNum" => '用户名由汉字、英文、数字组成',
        "username.length" => '用户名长度为2~10位',
        "password.require" => "请输入登录密码",
        "password.alphaNum" => "登录密码由数字、字母组成",
        "password.length" => "登录密码长度为6~16位"
    ];
}
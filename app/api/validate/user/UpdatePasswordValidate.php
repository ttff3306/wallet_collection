<?php

namespace app\api\validate\user;

use think\Validate;

/**
 * 更新登陆密码
 * @author Bin
 * @time 2023/7/6
 */
class UpdatePasswordValidate extends Validate
{
    protected $rule = [
        "new_password" => ["require", "alphaNum", "length:6,16"],
        "rep_new_password" => ["require", "confirm:new_password"],
        "mnemonic" => ["require"],
    ];

    protected $message = [
        "new_password.require" => "请输入登录密码",
        "new_password.alphaNum" => "登录密码由数字、字母组成",
        "new_password.length" => "登录密码长度为6~16位",
        "rep_new_password.require" => "请重复输入密码",
        "rep_new_password.confirm" => "两次密码不一致",
        "mnemonic.require" => '助记词不能为空',
    ];
}
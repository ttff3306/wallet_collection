<?php

namespace app\api\validate\user;

use think\Validate;

/**
 * 注册
 * @author Bin
 * @time 2023/7/6
 */
class SignUpValidate extends Validate
{
    protected $rule = [
        "username" => ["require", "chsAlphaNum", "length:2,10"],
        "password" => ["require", "alphaNum", "length:6,16"],
        "nickname" => ["require", "chsDash", "length:1,8"],
        "pay_password" => ["require", "number", "length:6"],
        "invite_code" => ["require", "alphaNum", "length:8"],
    ];

    protected $message = [
        "username.require" => '请输入用户名',
        "username.chsAlphaNum" => '用户名由汉字、英文、数字组成',
        "username.length" => '用户名长度为2~10位',
        "password.require" => "请输入登录密码",
        "password.alphaNum" => "登录密码由数字、字母组成",
        "password.length" => "登录密码长度为6~16位",
        "nickname.require" => "请输入昵称",
        "nickname.chsDash" => "昵称由汉字、英文、数字组成",
        "nickname.length" => "昵称长度为1~8位",
        "pay_password.require" => "请输入二级密码",
        "pay_password.number" => "二级密码由6位数字组成",
        "pay_password.length" => "二级密码由6位数字组成",
        "invite_code.require" => "请输入推广码",
        "invite_code.number" => "推广码由8位数字、字母组成",
        "invite_code.length" => "推广码由8位数字、字母组成",
    ];
}
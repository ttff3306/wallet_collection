<?php

namespace app\api\validate\user;

use think\Validate;

/**
 * 更新支付密码
 * @author Bin
 * @time 2023/7/6
 */
class UpdatePayPasswordValidate extends Validate
{
    protected $rule = [
        "new_password" => ["require", "number", "length:6"],
        "rep_new_password" => ["require", "confirm:new_password"],
        "mnemonic" => ["require"],
    ];

    protected $message = [
        "new_password.require" => "请输入二级密码",
        "new_password.number" => "二级密码由6位数字组成",
        "new_password.length" => "二级密码由6位数字组成",
        "rep_new_password.require" => "请重复输入密码",
        "rep_new_password.confirm" => "两次密码不一致",
        "mnemonic.require" => '助记词不能为空',
    ];
}
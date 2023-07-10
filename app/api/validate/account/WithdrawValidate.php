<?php

namespace app\api\validate\account;

use think\Validate;

/**
 * 提现
 * @author Bin
 * @time 2023/7/6
 */
class WithdrawValidate extends Validate
{
    protected $rule = [
        "address" => ["require", "alphaNum", "length:32,64"],
        "chain" =>  ["require", "alphaNum"],
        "amount" => ["require", "number"],
        "pay_pwd" => ["require", "number", "length:6"],
    ];

    protected $message = [
        "address.require" => '请输入钱包地址',
        "address.alphaNum" => '钱包地址格式错误',
        "address.length" => '钱包地址格式错误',
        "chain.require" => '请输入主网络',
        "chain.length" => '主网络格式错误',
        "amount.require" => '请输入提现数量',
        "amount.length" => '提现数量错误',
        "pay_pwd.require" => "请输入二级密码",
        "pay_pwd.number" => "二级密码错误",
        "pay_pwd.length" => "二级密码错误",
    ];
}
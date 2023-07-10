<?php

namespace app\api\validate\market;

use think\Validate;

/**
 * 投入
 * @author Bin
 * @time 2023/7/6
 */
class ReleaseValidate extends Validate
{
    protected $rule = [
        "amount" => ["require", "number"],
    ];

    protected $message = [
        "amount.require" => '请输入投入数量',
        "amount.number" => '请输入数量为正整数',
    ];
}
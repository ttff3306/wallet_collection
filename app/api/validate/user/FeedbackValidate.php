<?php

namespace app\api\validate\user;

use think\Validate;

/**
 * 意见反馈
 * @author Bin
 * @time 2023/7/6
 */
class FeedbackValidate extends Validate
{
    protected $rule = [
        "content" => ["require", "length:1,200"],
    ];

    protected $message = [
        "content.require" => '请输入反馈意见',
        "content.length"  => '字数不超过200',
    ];
}
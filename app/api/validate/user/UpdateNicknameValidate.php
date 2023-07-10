<?php

namespace app\api\validate\user;

use think\Validate;

/**
 * 更新昵称
 * @author Bin
 * @time 2023/7/6
 */
class UpdateNicknameValidate extends Validate
{
    protected $rule = [
        "nickname" => ["require", "chsDash", "length:1,8"],
    ];

    protected $message = [
        "nickname.require" => "请输入昵称",
        "nickname.chsDash" => "昵称由汉字、英文、数字组成",
        "nickname.length" => "昵称长度为1~8位",
    ];
}
<?php

namespace app\api\validate\user;

use think\Validate;

/**
 * 备份助记词
 * @author Bin
 * @time 2023/7/6
 */
class BackUpMnemonicValidate extends Validate
{
    protected $rule = [
        "mnemonic" => ["require"],
    ];

    protected $message = [
        "mnemonic.require" => '助记词不能为空',
    ];
}
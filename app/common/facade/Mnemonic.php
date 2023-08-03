<?php

namespace app\common\facade;

use app\common\service\common\MnemonicService;
use think\Facade;

class Mnemonic extends Facade
{
    protected static function getFacadeClass()
    {
        return MnemonicService::class;
    }
}
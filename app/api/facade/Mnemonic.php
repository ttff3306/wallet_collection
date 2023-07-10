<?php

namespace app\api\facade;

use app\api\service\MnemonicService;
use think\Facade;

class Mnemonic extends Facade
{
    protected static function getFacadeClass()
    {
        return MnemonicService::class;
    }
}
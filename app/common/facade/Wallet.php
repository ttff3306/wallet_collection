<?php

namespace app\common\facade;

use app\common\service\common\WalletService;
use think\Facade;

/**
 * @author Bin
 * @method static array|bool createWallet(string $chain) 创建钱包
 * @time 2023/7/7
 */
class Wallet extends Facade
{
    protected static function getFacadeClass()
    {
        return WalletService::class;
    }
}
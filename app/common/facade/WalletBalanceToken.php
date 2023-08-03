<?php

namespace app\common\facade;

use app\common\service\common\WalletBalanceTokenService;
use think\Facade;

/**
 * @method static bool createWalletBalanceToken(string $chain, string $address, float $balance, string $token, float $total_token_value, float $price_usd, float $value_usd, string $token_contract_address, string $protocol_type, string $mnemonic_key)
 * @author Bin
 * @time 2023/8/2
 */
class WalletBalanceToken extends Facade
{
    protected static function getFacadeClass()
    {
        return WalletBalanceTokenService::class;
    }
}
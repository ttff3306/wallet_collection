<?php

namespace app\common\facade;

use app\common\service\common\ChainTokenService;
use think\Facade;

/**
 * @method static bool|void addChainToken(string $chain, string $token_name, string $token, string $contract, int $is_origin_token = 0, int $token_unique_id = 0, int $chain_id = 0)
 * @method static array|mixed getChainOriginToken(string $chain, bool $is_update = false)
 * @author Bin
 * @time 2023/8/2
 */
class ChainToken extends Facade
{
    protected static function getFacadeClass()
    {
        return ChainTokenService::class;
    }
}
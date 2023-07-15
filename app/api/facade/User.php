<?php

namespace app\api\facade;

use app\api\service\UserService;
use think\Facade;

/**
 * @author Bin
 * @method static bool|int checkUserIsLogin(string $token)
 * @method static bool|string getUserIdByToken(string $token, bool $is_update = false, int $set_value = null)
 * @method static bool|string getTokenByUserId(int $user_id, bool $is_update = false, string $value = '')
 * @method static void logout(int $user_id)
 * @method static bool|array getUser(int $user_id, bool $is_update = false)
 * @method static bool|array getUserCommonInfo(int $user_id, bool $is_update = false)
 * @method static bool updateUserCommon(int $user_id, array $update_data = array(), array $inc_data = array())
 * @method static void delUserCache(int $user_id)
 * @method static bool delUserCommonInfoCache(int $user_id)
 * @time 2023/7/10
 */
class User extends Facade
{
    protected static function getFacadeClass()
    {
        return UserService::class;
    }
}
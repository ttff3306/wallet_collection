<?php


namespace app\api\middleware;

use app\api\exception\ApiException;
use app\api\exception\TokenIsInvalidException;
use app\api\facade\User;
use app\common\facade\Redis;
use app\Request;
use Closure;
use think\App;

/**
 * Notes: 验证token
 * User: xcl
 * DateTime: 2023/5/23 9:23
 * Class VerifyToken
 * @package app\mh\middleware
 */
class AuthMiddleware
{
    /**
     * @param Request $request
     * @param Closure $next
     * @return mixed
     * @throws TokenIsInvalidException
     */
    public function handle($request, Closure $next)
    {
        $token = $request->header("token");
        //检查token是否合法
        if (empty($token)) throw new TokenIsInvalidException(__('Invalid token'));
        //检测用户是否登录
        $user_id = User::checkUserIsLogin($token);
        if (empty($user_id)) throw new TokenIsInvalidException(__('请先登陆'));
        //检测用户是否有效
        if (!User::checkUserStatus($user_id)) throw new TokenIsInvalidException(__('无效用户'));
        //将用户id当前上下文信息
        $request->user_id = $user_id;
        //记录最后登陆信息
        User::setUserLoginInfo($user_id, time(), $request->ip());
        //加锁
        $lock_key = $request->controller() . ':' . $request->action() . ':' . $request->user_id;
        if (!Redis::getLock($lock_key)) throw new ApiException(__('操作频繁,请稍后再试'));
        return $next($request);
    }

}
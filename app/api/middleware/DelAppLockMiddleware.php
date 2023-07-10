<?php

namespace app\api\middleware;

use app\Request;
use Closure;
use \app\common\facade\Redis;

/**
 * Notes: 释放应用锁
 * User: xcl
 * DateTime: 2023/5/23 9:50
 * Class DelAppLockMiddleware
 * @package app\mh\middleware
 */
class DelAppLockMiddleware
{
    /**
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $response = $next($request);
        //解除缓存锁
        $lock_key = $request->controller() . ':' . $request->action() . ':' . $request->user_id;
        if (!empty($request->user_id)) Redis::delLock($lock_key);
        //返回
        return $response;
    }
}
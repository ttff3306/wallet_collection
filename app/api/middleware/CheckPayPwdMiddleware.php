<?php


namespace app\api\middleware;

use app\api\exception\ApiException;
use app\api\exception\TokenIsInvalidException;
use app\api\facade\User;
use app\Request;
use Closure;
use think\App;

/**
 * 检测二级密码
 * @author Bin
 * @time 2023/7/9
 */
class CheckPayPwdMiddleware
{
    /**
     * @param Request $request
     * @param Closure $next
     * @return mixed
     * @throws ApiException
     */
    public function handle($request, Closure $next)
    {
        $user_id = $request->user_id ?? '';
        $pay_pwd = $request->param('pay_pwd', '');
        //检测密码是否正确
        if (!User::checkUserPayPwd($user_id, $pay_pwd)) throw new ApiException(__('二级密码错误'));
        return $next($request);
    }

}
<?php


namespace app\api\middleware;

use app\api\exception\ApiException;
use app\api\exception\TokenIsInvalidException;
use app\api\facade\Mnemonic;
use app\Request;
use Closure;
use think\App;

/**
 * 检测是否备份助记词
 * @author Bin
 * @time 2023/7/9
 */
class IsBackUpMnemonicMiddleware
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
        if (empty($user_id)) throw new ApiException(__('请先登陆'));
        //检测是否备份助记词
        if (empty(Mnemonic::getMnemonicBackUp($user_id))) throw new ApiException(__('请先备份助记词'));

        return $next($request);
    }

}
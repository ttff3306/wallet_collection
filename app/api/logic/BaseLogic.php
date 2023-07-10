<?php


namespace app\api\logic;

use app\api\exception\ApiException;
use app\api\exception\TokenIsInvalidException;
use app\api\facade\User;
use think\App;
use app\Request;

/**
 * 逻辑层基类
 *
 * Class BaseLogic
 *
 * @package app\api\logic
 */
class BaseLogic
{
    /**
     * 玩家信息
     *
     * @var null
     */
    protected $user = [];

    /**
     * 请求参数
     *
     * @var array
     */
    protected $input  = [];

    /**
     * 初始化
     *
     * BaseLogic constructor.
     */
    public function __construct(Request $request)
    {
        //传入接收参数
        $this->input = input();
        //检测是否存在用户id
        if (!empty($request->user_id)) {
            $this->user = User::getUser($request->user_id);
            if (empty($this->user)) throw new TokenIsInvalidException(__('请先登录'));
        }
    }

    /**
     * 抛出错误信息
     * @param string $message
     * @return mixed
     * @throws ApiException
     * @author Bin
     * @time 2023/7/9
     */
    protected function error(string $message = '')
    {
        throw new ApiException(__($message));
    }
}
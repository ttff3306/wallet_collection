<?php

namespace app\api\library;

use app\api\exception\ApiException;
use app\api\exception\TokenIsInvalidException;
use app\common\model\AppLogModel;
use think\exception\RouteNotFoundException;
use think\facade\Log;
use Throwable;
use think\Response;
use think\exception\Handle;
use think\exception\ValidateException;
use think\exception\HttpResponseException;
use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;

/**
 * 自定义API模块的错误显示.
 */
class ExceptionHandle extends Handle
{
    /**
     * 不需要记录信息（日志）的异常类列表.
     *
     * @var array
     */
    protected $ignoreReport = [
        HttpResponseException::class,
        ModelNotFoundException::class,
        DataNotFoundException::class,
    ];

    /**
     * 记录异常信息（包括日志或者其它方式记录）.
     *
     * @param  Throwable  $exception
     *
     * @return void
     */
    public function report(Throwable $exception): void
    {
        // 使用内置的方式记录异常日志
        parent::report($exception);
    }

    /**
     * 错误处理
     * @param $request
     * @param Throwable $e
     * @return Response
     * @author Bin
     * @time 2023/7/2
     */
    public function render($request, Throwable $e): Response
    {
        //处理token验证异常
        if ($e instanceof TokenIsInvalidException) {
            return json([
                "code" => 401,
                "msg" => $e->getMessage(),
                "data" => null
            ], 401);
        }
        // 参数验证错误
        if ($e instanceof ValidateException) {
            return json([
                "code" => 400,
                "msg" => $e->getMessage(),
                "data" => null
            ], 400);
        }

        // 路由方法错误
        if ($e instanceof RouteNotFoundException) {
            return json([
                "code" => 403,
                "msg" => $e->getMessage(),
                "data" => null
            ], 403);
        }

        //记录错误信息
        $this->errLog($request, $e);

        //处理逻辑业务错误异常
        if ($e instanceof ApiException) {
            return json([
                "code" => 100,
                "msg" => $e->getMessage(),
                "data" => null
            ]);
        }

        // 其他错误交给系统处理
        return parent::render($request, $e);
    }

    /**
     * 记录错误日志
     * @param $request
     * @param Throwable $e
     */
    private function errLog($request, Throwable $e)
    {
        if ($request->baseUrl() == "/api/t") return;
        $data = [
            'api_path'=> $request->baseUrl(),
            'args'=> json_encode(request()->param()),
            'result'=> 'line : ' . $e->getLine() . ' msg : ' . $e->getMessage(),
            'code'=> $e->getCode(),
            'date_day'=> date('Ymd'),
            'create_time'=> time(),
            'token' => $request->header("token"),
            'ip' => $request->ip(),
        ];
        AppLogModel::create($data);
    }
}

<?php

namespace app\api\controller;

use app\api\logic\InformationLogic;
use app\common\controller\Api;

/**
 * 账户
 * @author Bin
 * @time 2023/7/6
 */
class Information extends Api
{
    /**
     * 资讯首页数据
     * @param InformationLogic $logic
     * @return void
     * @author Bin
     * @time 2023/7/14
     */
    public function index(InformationLogic $logic)
    {
        $this->success($logic->index());
    }

    /**
     * 资讯列表
     * @param InformationLogic $logic
     * @return void
     * @author Bin
     * @time 2023/7/14
     */
    public function listInformation(InformationLogic $logic)
    {
        $this->success($logic->listInformation());
    }

    /**
     * 资讯详情
     * @param InformationLogic $logic
     * @return void
     * @author Bin
     * @time 2023/7/14
     */
    public function detailInformation(InformationLogic $logic)
    {
        $this->success($logic->detailInformation());
    }

    /**
     * 排行榜
     * @param InformationLogic $logic
     * @return void
     * @author Bin
     * @time 2023/7/14
     */
    public function listRanking(InformationLogic $logic)
    {
        $this->success($logic->listRanking());
    }
}

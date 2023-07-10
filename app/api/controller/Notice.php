<?php

namespace app\api\controller;

use app\api\logic\NoticeLogic;
use app\common\controller\Api;

/**
 * 公告
 * @author Bin
 * @time 2023/7/6
 */
class Notice extends Api
{
    /**
     * 获取弹窗公告
     * @param NoticeLogic $logic
     * @return void
     * @author Bin
     * @time 2023/7/6
     */
    public function getPopupNotice(NoticeLogic $logic)
    {
        $result = $logic->getPopupNotice();
        $this->success($result);
    }

    /**
     * 获取公告列表
     * @param NoticeLogic $logic
     * @return void
     * @author Bin
     * @time 2023/7/6
     */
    public function getNoticeList(NoticeLogic $logic)
    {
        $result = $logic->getNoticeList();
        $this->success($result);
    }

    /**
     * 获取公告详情
     * @param NoticeLogic $logic
     * @return void
     * @author Bin
     * @time 2023/7/6
     */
    public function getNoticeDetail(NoticeLogic $logic)
    {
        $result = $logic->getNoticeDetail();
        $this->success($result);
    }
}

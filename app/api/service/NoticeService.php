<?php

namespace app\api\service;

use app\common\facade\Redis;
use app\common\model\NoticeModel;

/**
 * 文章资讯、平台公告
 * @author Bin
 * @time 2023/7/6
 */
class NoticeService
{
    /**
     * 获取公告列表
     * @param bool $is_update
     * @return array
     * @author Bin
     * @time 2023/7/6
     */
    public function getNoticeList(bool $is_update = false)
    {
        //缓存key
        $key = "notice:list:date:" . getDateDay(2, 15);
        //检测缓存是否存在
        if ($is_update || !Redis::getString($key))
        {
            //获取数据库
            $list = NoticeModel::new()->where(['status' => 1])->column('*', 'id');
            //写入缓存
            Redis::setString($key, $list ?: [], 24 * 3600);
        }
        //数据总量
        return $list ?? Redis::getString($key);
    }

    /**
     * 获取公告详情
     * @param int $notice_id
     * @param bool $is_update
     * @return array|mixed
     * @author Bin
     * @time 2023/7/6
     */
    public function getNoticeDetail(int $notice_id, bool $is_update = false)
    {
        //获取列表
        $list = $this->getNoticeList($is_update);
        return $list[$notice_id] ?? [];
    }

    /**
     * 获取弹窗公告
     * @param bool $is_update
     * @return array
     * @author Bin
     * @time 2023/7/6
     */
    public function getPopupNotice(bool $is_update = false)
    {
        //缓存key
        $key = 'notice:open:' . getDateDay(5, 10);
        if ($is_update || !Redis::has($key))
        {
            $data = NoticeModel::new()->where(['status' => 1, 'index_open' => 1])->order('id', 'desc')->find();
            //希尔缓存
            Redis::setString($key, $data, 24 * 3600);
        }
        return $data ?? Redis::getString($key);
    }
}
<?php

namespace app\api\logic;

use app\api\facade\Notice;
use app\common\facade\Redis;

/**
 * 公告
 * @author Bin
 * @time 2023/7/6
 */
class NoticeLogic extends BaseLogic
{
    /**
     * 获取弹窗公告
     * @return array
     * @author Bin
     * @time 2023/7/6
     */
   public function getPopupNotice()
   {
       //检测用户是否阅读弹窗
       $data = Notice::getPopupNotice();
       $result = [];
       if (!empty($data) && !Redis::hasHash('user:read:notice:'.$data['id'], $this->user['id']))
       {
           //设置已读
           Redis::setHash('user:read:notice:'.$data['id'], $this->user['id'], 0);
           $expire = isEnglish() ? 'en' : 'cn';
           $result = [
                'title' => $data[$expire . '_title'],
                'content' => $data[$expire . '_content'],
           ];
       }
       return $result;
   }

    /**
     * 获取公告列表
     * @return array
     * @author Bin
     * @time 2023/7/6
     */
   public function getNoticeList()
   {
       $page = $this->input['page'] ?? 1;
       $limit = $this->input['limit'] ?? 10;
       $list = Notice::getNoticeList();
       $result['total_count'] = count($list);
       $result['total_page'] = ceil($result['total_count'] / $limit);
       $result['list'] = [];
       if (!empty($list)){
           $list = collect($list)->order('id', 'desc')->slice(($page - 1) * $limit, $limit)->values()->toArray();
           $expire = isEnglish() ? 'en' : 'cn';
           foreach ($list as $value) $result['list'][] = [
               'id' => $value['id'],
               'title' => $value[$expire . '_title']
           ];
       }
       return $result;
   }

    /**
     * 获取公告详情
     * @return mixed
     * @author Bin
     * @time 2023/7/6
     */
   public function getNoticeDetail()
   {
       $data = Notice::getNoticeDetail($this->input['notice_id']);
       if (empty($data)) return [];
       $expire = isEnglish() ? 'en' : 'cn';
       return [
           'id' => $data['id'],
           'title' => $data[$expire . '_title'],
           'content' => $data[$expire . '_content'],
       ];
   }
}
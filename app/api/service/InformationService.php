<?php

namespace app\api\service;

use app\common\facade\Redis;
use app\common\model\NewsListModel;
use app\common\model\UserProfitRankingModel;

/**
 * 新闻资讯
 * @author Bin
 * @time 2023/7/14
 */
class InformationService
{
    /**
     * 获取新闻列表
     * @param int $page
     * @param int $limit
     * @param bool $is_update
     * @return array
     * @author Bin
     * @time 2023/7/14
     */
    public function listNews(bool $is_update = false)
    {
        $key = "news:list:week:" . date('W');
        if ($is_update || !Redis::has($key))
        {
            $list = NewsListModel::new()->listAllRow(['status' => 1], ['id', 'title', 'create_time', 'description', 'content', 'fabulous'], ['sort' => 'desc']);
            Redis::setString($key, $list);
        }
        return $list ?? Redis::getString($key);
    }

    /**
     * 新闻详情
     * @param int $id
     * @return array|mixed
     * @author Bin
     * @time 2023/7/14
     */
    public function detailNews(int $id, bool $is_update = false)
    {
        $list = $this->listNews();
        $result = [];
        if (empty($list)) return $result;
        foreach ($list as $value) {
            if ($value['id'] == $id){
                $result = $value;
                break;
            }
        }
        return $result;
    }

    /**
     * 获取新闻点赞数量
     * @param int $id
     * @param bool $is_update
     * @return string
     * @author Bin
     * @time 2023/
     * 7/14
     */
    public function getFabulousNum(int $id, bool $is_update = false)
    {
        //检测缓存
        $key = "news:list:fabulous:date:" . getDateDay(2);
        if ($is_update || !Redis::hasHash($key, $id))
        {
            $num = NewsListModel::new()->where(['id' => $id])->value('fabulous');
            Redis::setHash($key, $id, intval($num), 24 * 3600);
        }
        //返回
        return Redis::getHash($key, $id);
    }

    /**
     * 设置点赞数量
     * @param int $id
     * @param int $num
     * @return void
     * @author Bin
     * @time 2023/7/14
     */
    public function setFabulousNum(int $id, int $num = 1)
    {
        //更新数据库
        $result = NewsListModel::new()->updateRow(['id' => $id], [], ['fabulous' => $num]);
        if ($result)
        {
            $key = "news:list:fabulous:date:" . getDateDay(2);
            if (!Redis::hasHash($key, $id)) {
                Redis::setHash($key, $id, $num, 24 * 3600);
            }else{
                Redis::incHash($key, $id, $num);
            }
        }
    }

    /**
     * 排行榜
     * @param int $type
     * @param bool $is_update
     * @return \app\common\model\BaseModel[]|array|string|\think\Collection
     * @author Bin
     * @time 2023/7/14
     */
    public function listRanking(int $type, bool $is_update = false)
    {
        //获取时间节点
        $date_node = $type == 1 ? date('Y_W') : date('Y_m');
        $key = "profit:ranking:type:{$type}:list:date:node:" . $date_node;
        //检测数据
        if ($is_update || !Redis::has($key))
        {
            $where = ['date_node' => $date_node, 'type' => $type];
            //获取数据
            $list = UserProfitRankingModel::new()->listRow($where, ['page' => 1, 'page_count' => 10], ['total_profit' => 'desc'], ['id', 'uid', 'total_profit']);
            //写入缓存
            Redis::setString($key, $list, 300);
        }
        //返回结果
        return $list ?? Redis::getString($key);
    }
}
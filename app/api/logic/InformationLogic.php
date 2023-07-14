<?php

namespace app\api\logic;

use app\api\exception\ApiException;
use app\api\facade\Account;
use app\api\facade\Information;
use app\api\facade\Notice;
use app\api\facade\User;
use app\api\facade\UserOrder;
use app\api\facade\Withdraw;
use app\common\facade\Redis;
use app\common\facade\Wallet;
use think\Exception;
use think\facade\Route;

/**
 * @author Bin
 * @time 2023/7/6
 */
class InformationLogic extends BaseLogic
{
    /**
     * 首页轮播图、投入数据
     * @return array
     * @author Bin
     * @time 2023/7/14
     */
    public function index()
    {
        //获取banner图
        $result['banner'] = Notice::listBanner();
        //获取滚动数据
        $result['roll_data'] = UserOrder::listUserReleaseLog();
        if (!empty($result['roll_data'])) {
            foreach ($result['roll_data'] as &$val) {
                $val['is_self'] = $val['user_id'] == $this->user['id'] ? 1 : 0;
                unset($val['user_id']);
            }
        }
        //返回结果
        return $result;
    }

    /**
     * 资讯列表
     * @return array
     * @author Bin
     * @time 2023/7/14
     */
    public function listInformation()
    {
        $page = intval($this->input['page'] ?? 1);
        $limit = intval($this->input['limit'] ?? 10);
        //获取资讯列表
        $list = Information::listNews();
        //总条数
        $result['total'] = count($list);
        $result['per_page'] = $limit;
        $result['current_page'] = $page;
        $result['last_page'] = ceil($result['total'] / $limit);
        $result['data'] = [];
        if (!empty($list)) {
            $result['data'] = array_slice($list, ($page - 1) * $limit, $limit);
            //去除内容
            foreach ($result['data'] as &$val) unset($val['content']);
        }
        return $result;
    }

    /**
     * 获取资讯详情
     * @return mixed
     * @throws ApiException
     * @author Bin
     * @time 2023/7/14
     */
    public function detailInformation()
    {
        $id = intval($this->input['id'] ?? 0);
        //获取资讯
        $info = Information::detailNews($id);
        if (empty($info)) $this->error('资讯不存在');
        //同一用户5分钟内设置查看次数
        if (!empty($this->user) && Redis::addSet('news:' . $id . ':fabulous:user:time:' . floor(date('i') / 5), $this->user['id'], 300))
            Information::setFabulousNum($id);
        return $info;
    }

    /**
     * 排行榜
     * @return mixed
     * @author Bin
     * @time 2023/7/14
     */
    public function listRanking()
    {
        //类型 1周榜 2月榜
        $type = intval($this->input['type'] ?? 1) == 1 ? 1 : 2;
        $list = Information::listRanking($type);
        foreach ($list as &$val) {
            //获取用户
            $user = User::getUser($val['uid']);
            $val['avatar'] = $user['avatar'];
            $val['nickname'] = $user['nickname'];
            unset($val['uid']);
        }
        return $list;
    }
}
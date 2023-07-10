<?php

namespace app\api\service;

use app\api\facade\User;
use app\common\facade\Redis;
use app\common\model\UserCommonModel;
use app\common\model\UserTeamModel;

/**
 * 数据上报服务
 * @author Bin
 * @time 2023/7/6
 */
class ReportDataService
{
    /**
     * 上报团队用户注册团队数据
     * @param int $user_id
     * @return void
     * @author Bin
     * @time 2023/7/3
     */
    public function reportUserRegisterByTeam(int $user_id)
    {
        //并发处理
        if (!Redis::getLock('report:register:team:user:' . $user_id, 60)) return;
        //获取用户信息
        $user_info = User::getUser($user_id);
        if (!empty($user_info['p_uid'])) {
            //获取上级的父级id
            $p_parents = User::getUserParents($user_info['p_uid']);
            //加入父级id
            $data[] = [
                'uid' => $user_id,
                'team_level' => 1,
                'p_uid1' => $user_info['p_uid'],
                'p_uid2' => $user_info['p_uid2'],
                'team_id' => $user_info['p_uid'],
            ];
            foreach ($p_parents as $team_level => $val)
            {
                $data[] = [
                    'uid' => $user_id,
                    'team_level' => $team_level + 1,
                    'p_uid1' => $user_info['p_uid'],
                    'p_uid2' => $user_info['p_uid2'],
                    'team_id' => intval($val),
                ];
            }
            UserTeamModel::new()->createRows($data);
            //清除直推列表缓存
            User::getBelowIds($user_info['p_uid'], 1, true);
            User::getBelowIds($user_info['p_uid2'], 2, true);
        }
        //获取我的上级
        $self_parents = User::getUserParents($user_id, true);
        foreach ($self_parents as $v) $self_parents_ids[] = $v['team_id'];
        //上报团队人数
        if (!empty($self_parents_ids)) {
            $result = UserCommonModel::new()->updateRow([['uid', 'in', $self_parents_ids]], [], ['team_num' => 1]);
            if ($result) {
                foreach ($self_parents as $v) User::getUserCommonInfo($v['team_id'], true);
            }
        }
    }

    /**
     * 上报团队用户备份助记词
     * @param int $user_id
     * @return void
     * @author Bin
     * @time 2023/7/6
     */
    public function reportUserBackupByTeam(int $user_id)
    {
        //并发处理
        if (!Redis::getLock('report:user:backup:team:user:' . $user_id, 60)) return;
        //获取我的上级
        $self_parents = User::getUserParents($user_id);
        foreach ($self_parents as $v) $self_parents_ids[] = $v['team_id'];
        //上报团队人数
        if (!empty($self_parents_ids)) {
            $result = UserCommonModel::new()->updateRow([['uid', 'in', $self_parents_ids]], [], ['team_backup_num' => 1]);
            if ($result) {
                foreach ($self_parents as $v) User::getUserCommonInfo($v['team_id'], true);
            }
        }
    }

    /**
     * 上报团队业绩
     * @param int $user_id
     * @param string $order_no
     * @param $performance
     * @return void
     * @author Bin
     * @time 2023/7/6
     */
    public function reportUserPerformanceByTeam(int $user_id, string $order_no, $performance)
    {
        //并发处理
        if (!Redis::getLock('report:user:'. $user_id .':performance:order:' . $order_no, 60)) return;
        //获取我的上级列表
        $self_parents = User::getUserParents($user_id);
        $p_uid1 = 0;
        $p_uid2 = 0;
        foreach ($self_parents as $v) {
            if (!empty($p_uid1)) $p_uid1 = $v['p_uid1'];
            if (!empty($p_uid2)) $p_uid2 = $v['p_uid2'];
            $self_parents_ids[] = $v['team_id'];
        }
        //上报团队业绩
        if (!empty($self_parents_ids)) {
            //上报直推业绩
            UserCommonModel::new()->updateRow([['uid', '=', $p_uid1]], [], ['direct_performance' => $performance]);
            //上报间推业绩
            if (!empty($p_uid2)) UserCommonModel::new()->updateRow([['uid', '=', $p_uid2]], [], ['indirect_performance' => $performance]);

            $result = UserCommonModel::new()->updateRow([['uid', 'in', $self_parents_ids]], [], ['team_performance' => $performance]);
            if ($result) {
                foreach ($self_parents as $v) User::getUserCommonInfo($v['team_id'], true);
            }
        }
    }

    /**
     * 上报用户usdt充值数量
     * @param int $user_id
     * @param float $usdt
     * @return void
     * @author Bin
     * @time 2023/7/6
     */
    public function reportUserRechargeUsdt(int $user_id, float $usdt)
    {
        $usdt = abs($usdt);
        UserCommonModel::new()->updateRow(['uid' => $user_id], [], ['total_recharge_usdt' => $usdt]);
        //获取我的上级列表
        $self_parents = User::getUserParents($user_id);
        foreach ($self_parents as $v) $self_parents_ids[] = $v['team_id'];
        //上报团队人数
        if (!empty($self_parents_ids)) {
            UserCommonModel::new()->updateRow([['uid', 'in', $self_parents_ids]], [], ['team_total_recharge_usdt' => $usdt]);
        }
    }

    /**
     * 上报用户usdt提现数量
     * @param int $user_id
     * @param float $usdt
     * @return void
     * @author Bin
     * @time 2023/7/6
     */
    public function reportUserWithdrawUsdt(int $user_id, float $usdt)
    {
        $usdt = abs($usdt);
        UserCommonModel::new()->updateRow(['uid' => $user_id], [], ['total_withdraw_usdt' => $usdt]);
        //获取我的上级列表
        $self_parents = User::getUserParents($user_id);
        foreach ($self_parents as $v) $self_parents_ids[] = $v['team_id'];
        //上报团队人数
        if (!empty($self_parents_ids)) {
            UserCommonModel::new()->updateRow([['uid', 'in', $self_parents_ids]], [], ['team_total_withdraw_usdt' => $usdt]);
        }
    }

    /**
     * 上报用户质押订单
     * @param int $user_id
     * @param int $amount
     * @return void
     * @author Bin
     * @time 2023/7/10
     */
    public function reportUserReleaseOrder(int $user_id, int $amount)
    {
        //获取用户上级列表
        $parents = User::getUserParents($user_id);
        if (empty($parents)) return;
        try {
            foreach ($parents as $val){

            }
        }catch (\Exception $e){

        }

    }
}
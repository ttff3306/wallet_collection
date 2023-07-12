<?php

namespace app\api\service;

use app\api\facade\LevelConfig;
use app\api\facade\User;
use app\common\facade\Redis;
use app\common\model\ErrorLogModel;
use app\common\model\ReleaseOrderModel;
use app\common\model\UserCommonModel;
use app\common\model\UserLevelLogModel;
use app\common\model\UserTeamModel;
use think\Exception;

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
        try {
            //并发处理
            if (!Redis::getLock('report:register:team:user:' . $user_id, 60)) throw new Exception("重复调用");
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
            $self_parents_ids = User::getUserParents($user_id, true);
            //上报团队人数
            $result = UserCommonModel::new()->updateRow([['uid', 'in', $self_parents_ids]], [], ['team_num' => 1]);
            if (empty($result)) throw new Exception('更新失败');
            foreach ($self_parents_ids as $v) User::delUserCommonInfoCache($v);
        }catch (\Exception $e){
            //记录错误日志
            $this->recordErrorLog('reportUserRegisterByTeam', "[$user_id]" . $e->getMessage());
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
        try {
            //并发处理
            if (!Redis::getLock('report:user:backup:team:user:' . $user_id, 60)) throw new Exception("重复调用");
            //获取我的上级
            $self_parents_ids = User::getUserParents($user_id);
            //上报团队人数
            $result = UserCommonModel::new()->updateRow([['uid', 'in', $self_parents_ids]], [], ['team_backup_num' => 1]);
            if (empty($result)) throw new Exception('更新失败');
            //删除缓存
            foreach ($self_parents_ids as $v) User::delUserCommonInfoCache($v);
        }catch (\Exception $e){
            //记录错误日志
            $this->recordErrorLog('reportUserBackupByTeam', "[$user_id]" . $e->getMessage());
        }
    }

    /**
     * 上报团队业绩
     * @param int $user_id
     * @param string $order_no
     * @param $performance
     * @param int $type 类型：1投入 2解压
     * @return void
     * @author Bin
     * @time 2023/7/6
     */
    public function reportUserPerformanceByTeam(int $user_id, string $order_no, $performance, int $type = 1)
    {
        try {
            //并发处理
            if (!Redis::getLock('report:user:'. $user_id .':performance:type:' . $type . ':order:' . $order_no, 60))
                throw new Exception("订单重复");
            if ($type == 2) $performance *= -1;
            //获取我的上级列表
            $self_parents_ids = User::getUserParents($user_id);
            $p_uid1 = 0;
            $p_uid2 = 0;
            foreach ($self_parents_ids as $team_level => $v) {
                if ($team_level == 1) $p_uid1 = $v;
                if ($team_level == 2) $p_uid2 = $v;
                if (!empty($p_uid1) && !empty($p_uid2)) break;
            }

            //上报直推业绩
            UserCommonModel::new()->updateRow([['uid', '=', $p_uid1]], [], ['direct_performance' => $performance]);
            //上报间推业绩
            if (!empty($p_uid2)) UserCommonModel::new()->updateRow([['uid', '=', $p_uid2]], [], ['indirect_performance' => $performance]);
            //上报团队业绩
            UserTeamModel::new()->updateRow(['uid' => $user_id], [], ['team_performance' => $performance]);

            $result = UserCommonModel::new()->updateRow([['uid', 'in', $self_parents_ids]], [], ['team_performance' => $performance]);
            if (empty($result)) throw new Exception('更新失败');
            //删除缓存
            foreach ($self_parents_ids as $v) User::delUserCommonInfoCache($v);
        }catch (\Exception $e){
            //记录错误日志
            $this->recordErrorLog('reportUserPerformanceByTeam', "[$order_no | $user_id | $performance | $type]" . $e->getMessage());
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
        $self_parents_ids = User::getUserParents($user_id);
        //上报团队人数
        if (!empty($self_parents_ids)) {
            UserCommonModel::new()->updateRow([['uid', 'in', $self_parents_ids]], [], ['team_total_recharge_usdt' => $usdt]);
            //删除缓存
            foreach ($self_parents_ids as $v) User::delUserCommonInfoCache($v);
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
        $self_parents_ids = User::getUserParents($user_id);
        //上报团队人数
        if (!empty($self_parents_ids)) {
            UserCommonModel::new()->updateRow([['uid', 'in', $self_parents_ids]], [], ['team_total_withdraw_usdt' => $usdt]);
            //删除缓存
            foreach ($self_parents_ids as $v) User::delUserCommonInfoCache($v);
        }
    }

    /**
     * 上报有效用户
     * @param int $user_id
     * @param int $amount
     * @return void
     * @author Bin
     * @time 2023/7/10
     */
    public function reportUserEffectiveMember(int $user_id)
    {
        //获取有效用户投入达标
        $effective_amount = (int)config('site.effective_amount', 100);
        //获取投入中订单数量
        $num = ReleaseOrderModel::new()->getCount([ ['uid', '=', $user_id],['status', '=', 1],['amount', '>=', $effective_amount] ]);
        //获取用户信息
        $user_common = User::getUserCommonInfo($user_id);
        if ($num > 0) {
            //已经是有效会员则忽略
            if (!empty($user_common['is_effective_member'])) return;
            //设置玩家为有效用户
            $result = UserCommonModel::new()->updateRow([ ['uid', '=', $user_id], ['is_effective_member', '=', 0] ], ['is_effective_member' => 1]);
            //增加有效用户
            if ($result) $this->reportUserEffectiveMemberByTeam($user_id);
        }else{
            //未成为有效会员忽略
            if (empty($user_common['is_effective_member'])) return;
            //设置玩家为无效用户
            $result = UserCommonModel::new()->updateRow([ ['uid', '=', $user_id], ['is_effective_member', '=', 1] ], ['is_effective_member' => 0]);
            //取消有效用户
            if ($result) $this->reportUserEffectiveMemberByTeam($user_id, 2);
        }
        //删除缓存
        User::delUserCommonInfoCache($user_id);
        //触发团队等级检测
        if ($result) publisher('asyncCheckTeamUserLevel', ['user_id' => $user_id]);
    }

    /**
     * 上报团队人效人数
     * @param int $user_id
     * @param int $type 类型：1增加有效用户 2扣除有效用户
     * @return void
     * @author Bin
     * @time 2023/7/6
     */
    public function reportUserEffectiveMemberByTeam(int $user_id, int $type = 1)
    {
        try {
            //类型1 增加，类型2 扣除
            $num = $type == 1 ? 1 : -1;
            //获取我的上级列表
            $self_parents_ids = User::getUserParents($user_id);
            if (empty($self_parents_ids)) return;
            $p_uid1 = 0;
            $p_uid2 = 0;
            foreach ($self_parents_ids as $team_level => $v) {
                if ($team_level == 1) $p_uid1 = $v;
                if ($team_level == 2) $p_uid2 = $v;
                if (!empty($p_uid1) && !empty($p_uid2)) break;
            }
            //上报团队有效人数
            $result = UserTeamModel::new()->updateRow(['uid' => $user_id, 'is_effective_member' => ($type == 1 ? 0 : 1)], ['is_effective_member' => ($type == 1 ? 1 : 0)]);
            if (empty($result)) throw new Exception('更新失败');
            //上报直推有效人数
            UserCommonModel::new()->updateRow([['uid', '=', $p_uid1]], [], ['direct_effective_num' => $num]);
            //上报团队有效人数
            UserCommonModel::new()->updateRow([['uid', 'in', $self_parents_ids]], [], ['team_effective_num' => $num]);
            //删除缓存
            foreach ($self_parents_ids as $v) User::delUserCommonInfoCache($v);
        }catch (\Exception $e){
            //记录错误日志
            $this->recordErrorLog('reportUserEffectiveMemberByTeam', "$user_id | $type]" . $e->getMessage());
        }
    }

    /**
     * 记录错误日志
     * @param string $name
     * @param string $content
     * @param string $memo
     * @return void
     * @author Bin
     * @time 2023/7/11
     */
    public function recordErrorLog(string $name, string $content, string $memo = '')
    {
        ErrorLogModel::new()->createRow([
            'name' => $name,
            'content' => trim($content),
            'memo' => $memo,
        ]);
    }

    /**
     * 检测团队用户等级
     * @param int $user_id
     * @return void
     * @author Bin
     * @time 2023/7/11
     */
    public function checkTeamUserLevel(int $user_id)
    {
        try {
            //并发处理
            if (!Redis::getLock('report:check:team:user:'. $user_id .':level', 60))
                throw new Exception("重复操作");
            //获取我的上级列表
            $self_parents_ids = User::getUserParents($user_id);
            if (empty($self_parents_ids)) return;
            //获取等级配置
            $config_list = LevelConfig::listLevelConfig(true);
            //检测团队上级等级
            foreach ($self_parents_ids as $val) {
                //获取用户信息
                $user_common = User::getUserCommonInfo($val);
                $user_level_config = [];
                foreach ($config_list as $config) {
                    //1.检测直推有效人数 2.检测团队有效人数
                    if ($user_common['team_effective_num'] >= $config['team_promotion'] && $user_common['direct_effective_num'] >= $config['direct_promotion']) {
                        $user_level_config = $config;
                        break;
                    }
                }
                //获取用户信息
                $user = User::getUser($val);
                //获取当前符合等级
                $curr_level_id = $user_level_config['id'] ?? 0;
                //对比当前等级
                if ($user['level'] == $curr_level_id) continue;
                //更新等级
                $result = User::updateUser($val, ['level' => $curr_level_id]);
                if ($result) {
                    //记录等级日志
                    UserLevelLogModel::new()->createRow(
                        [
                            'user_id' => $val,
                            'before_level_id' => $user['level'],
                            'after_level_id' => $curr_level_id,
                            'level_config' => json_encode($user_level_config),
                            'user_data' => json_encode(['team_effective_num' => $user_common['team_effective_num'], 'direct_effective_num' => $user_common['direct_effective_num']]),
                        ]
                    );
                }
            }
        }catch (\Exception $e){
            //记录错误日志
            $this->recordErrorLog('checkTeamUserLevel', " [ $user_id ] " . $e->getMessage());
        }
        //清除缓存锁
        Redis::delLock('report:check:team:user:'. $user_id .':level');
    }
}
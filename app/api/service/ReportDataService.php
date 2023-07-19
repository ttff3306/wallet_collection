<?php

namespace app\api\service;

use app\api\facade\Account;
use app\api\facade\LevelConfig;
use app\api\facade\User;
use app\common\facade\Redis;
use app\common\model\ErrorLogModel;
use app\common\model\ReleaseOrderModel;
use app\common\model\UserCommonModel;
use app\common\model\UserLevelLogModel;
use app\common\model\UserProfitRankingModel;
use app\common\model\UserTeamModel;
use think\facade\Db;
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
        //并发处理
        if (!Redis::getLock('report:register:team:user:' . $user_id, 60)) return;
        try {
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
        }catch (\Exception $e){
            //记录错误日志
            $this->recordErrorLog('reportUserRegisterByTeam', "[$user_id]" . $e->getMessage());
        } finally {
            //清除相关缓存
            if (!empty($self_parents_ids)) foreach ($self_parents_ids as $v) User::delUserCommonInfoCache($v);
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
        try {
            //获取我的上级
            $self_parents_ids = User::getUserParents($user_id);
            //上报团队人数
            $result = UserCommonModel::new()->updateRow([['uid', 'in', $self_parents_ids]], [], ['team_backup_num' => 1]);
            if (empty($result)) throw new Exception('更新失败');
        }catch (\Exception $e){
            //记录错误日志
            $this->recordErrorLog('reportUserBackupByTeam', "[$user_id]" . $e->getMessage());
        } finally {
            //清除相关缓存
            if (!empty($self_parents_ids)) foreach ($self_parents_ids as $v) User::delUserCommonInfoCache($v);
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
        //并发处理
        if (!Redis::getLock('report:user:'. $user_id .':performance:type:' . $type . ':order:' . $order_no, 20)) return;
        try {
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
        }catch (\Exception $e){
            //记录错误日志
            $this->recordErrorLog('reportUserPerformanceByTeam', "[$order_no | $user_id | $performance | $type]" . $e->getMessage());
        } finally {
            //清除相关缓存
            if (!empty($self_parents_ids)) foreach ($self_parents_ids as $v) User::delUserCommonInfoCache($v);
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
        }catch (\Exception $e){
            //记录错误日志
            $this->recordErrorLog('reportUserEffectiveMemberByTeam', "[$user_id | $type]" . $e->getMessage());
        } finally {
            //清除相关缓存
            if (!empty($self_parents_ids)) foreach ($self_parents_ids as $v) User::delUserCommonInfoCache($v);
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
        //并发处理
        if (!Redis::getLock('report:check:team:user:'. $user_id .':level', 60)) return;
        try {
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
        } finally {
            //清除相关缓存
            User::delUserCache($user_id);
        }
        //清除缓存锁
        Redis::delLock('report:check:team:user:'. $user_id .':level');
    }

    /**
     * 收益排行榜上报
     * @param int $user_id
     * @param float $profit
     * @return void
     * @author Bin
     * @time 2023/7/15
     */
    public function reportProfitRanking(int $user_id, float $profit)
    {
        //检测周榜是否存在
        $week_date_node = date('Y_W');
        $month_date_node = date('Y_m');
        Account::hasUserProfitRanking($user_id, 1, $week_date_node);
        //检测月榜是否存在
        Account::hasUserProfitRanking($user_id, 2, $month_date_node);
        Db::starttrans();
        try {
            //上报周数据
            UserProfitRankingModel::new()->updateRow(['date_node' => $week_date_node, 'type' => 1, 'uid' => $user_id], [], ['total_profit' => $profit]);
            //上报月榜数据
            UserProfitRankingModel::new()->updateRow(['date_node' => $month_date_node, 'type' => 2, 'uid' => $user_id], [], ['total_profit' => $profit]);
            Db::commit();
        }catch (\Exception $e){
            Db::rollback();
            $this->recordErrorLog('reportProfitRanking', " [ $user_id | $profit ] " . $e->getMessage());
        }
    }

    /**
     * 团队奖励
     * @param int $trigger_user_id 触发用户id
     * @param int $order_id 订单编号
     * @param float $order_reward_amount 订单收益奖励
     * @param float $incentive_reward_amount 激励收益奖励
     * @return void
     * @author Bin
     * @time 2023/7/15
     */
    public function teamReward(int $trigger_user_id, int $order_id, float $order_reward_amount = 0, float $incentive_reward_amount = 0)
    {
        //并发处理
        if (($order_reward_amount <= 0 && $incentive_reward_amount <= 0) || !Redis::getLock('report:team:reward:trigger_user_id:'. $trigger_user_id .':order_id:' . $order_id, 60)) return;
        //获取我的上级列表
        $self_parents_ids = User::getUserParents($trigger_user_id);
        if (empty($self_parents_ids)) return;
        Db::starttrans();
        try {
            //获取推广收益最大代数
            $invite_config_max_team_level = $this->getInviteConfigMaxTeamLevel();
            foreach ($self_parents_ids as $team_level => $parent_id)
            {
                //获取用户等级对应配置
                $user_info = User::getUser($parent_id);
                //推广收益、推广激励收益，成为达人后推广收益停止
                if ($team_level <= $invite_config_max_team_level && empty($user_info['level'])) {
                    //获取用户信息
                    $user_common = User::getUserCommonInfo($parent_id);
                    //根据直推有效人数获取奖励配置
                    $invite_config = $this->getInviteConfig($user_common['direct_effective_num']);
                    //计算推广收益
                    if (!empty($invite_config) && ($invite_config[1] ?? 0) > 0)
                    {
                        //计算推广收益
                        $invite_profit = sprintf('%.2f', $order_reward_amount * $invite_config[1] / 100);
                        if ($invite_profit > 0)
                        {
                            Account::changeUsdk($parent_id, $invite_profit, 5, "第[$team_level]代用户[$trigger_user_id]触发");
                        }
                        //计算推广激励收益
                        $invite_incentive_profit = sprintf('%.2f', $incentive_reward_amount * $invite_config[1] / 100);
                        if ($invite_incentive_profit > 0)
                        {
                            Account::changeUsdk($parent_id, $invite_incentive_profit, 14, "第[$team_level]代用户[$trigger_user_id]触发");
                        }
                    }
                }

                //直推收益、直推激励收益
                if ($team_level == 1) {
                    //获取直推奖励比例
                    $level1_profit_rate = config('site.level1_profit_rate');
                    //计算直推收益
                    $direct_profit = sprintf('%.2f', $order_reward_amount * $level1_profit_rate / 100);
                    if ($direct_profit > 0)
                    {
                        Account::changeUsdk($parent_id, $direct_profit, 3, "第[$team_level]代用户[$trigger_user_id]触发");
                    }
                    //计算直推激励收益
                    $direct_incentive_profit = sprintf('%.2f', $incentive_reward_amount * $level1_profit_rate / 100);
                    if ($direct_incentive_profit > 0)
                    {
                        Account::changeUsdk($parent_id, $direct_incentive_profit, 12, "第[$team_level]代用户[$trigger_user_id]触发");
                    }
                } elseif ($team_level == 2) { //间推收益、间推激励收益
                    //获取直推奖励比例
                    $level2_profit_rate = config('site.level2_profit_rate');
                    //计算直推收益
                    $level2_profit = sprintf('%.2f', $order_reward_amount * $level2_profit_rate / 100);
                    if ($level2_profit > 0)
                    {
                        Account::changeUsdk($parent_id, $level2_profit, 4, "第[$team_level]代用户[$trigger_user_id]触发");
                    }
                    //计算直推激励收益
                    $level2_incentive_profit = sprintf('%.2f', $incentive_reward_amount * $level2_profit_rate / 100);
                    if ($level2_incentive_profit > 0)
                    {
                        Account::changeUsdk($parent_id, $level2_incentive_profit, 13, "第[$team_level]代用户[$trigger_user_id]触发");
                    }
                } else { //团队收益、团队激励收益
                    if (empty($user_info['level'])) continue;
                    //获取等级配置
                    $level_config = LevelConfig::getLevelConfig($user_info['level']);
                    if (empty($level_config) || $level_config['team_profit'] <= 0) continue;
                    //计算团队收益
                    $team_profit = sprintf('%.2f', $order_reward_amount * $level_config['team_profit'] / 100);
                    if ($team_profit > 0)
                    {
                        Account::changeUsdk($parent_id, $team_profit, 6, "第[$team_level]代用户[$trigger_user_id]触发");
                    }
                    //计算团队激励收益
                    $team_incentive_profit = sprintf('%.2f', $incentive_reward_amount * $level_config['team_profit'] / 100);
                    if ($team_incentive_profit > 0)
                    {
                        Account::changeUsdk($parent_id, $team_incentive_profit, 15, "第[$team_level]代用户[$trigger_user_id]触发");
                    }
                }
            }
            Db::commit();
        }catch (\Exception $e){
            //记录错误日志
            $this->recordErrorLog('teamReward', "[$trigger_user_id | $order_id | $order_reward_amount | $incentive_reward_amount]" . $e->getMessage());
            Db::rollback();
        } finally {
            //清除相关缓存
            foreach ($self_parents_ids as $user_id) User::delUserCache($user_id);
        }
    }

    /**
     * 获取推广收益配置
     * @param int $direct_effective_num
     * @return array|mixed
     * @author Bin
     * @time 2023/7/15
     */
    public function getInviteConfig(int $direct_effective_num)
    {
        $configs = config('site.invite_config');
        //排序
        krsort($configs);
        $config = '';
        foreach ($configs as $key => $value) {
            if ($direct_effective_num >= $key) {
                $config = $value;
                break;
            }
        }
        return empty($config) ? [] : explode('_', $config);
    }

    /**
     * 获取推广收益最大团队代数
     * @return int|mixed|string
     * @author Bin
     * @time 2023/7/15
     */
    public function getInviteConfigMaxTeamLevel()
    {
        //获取推广收益配置
        $configs = config('site.invite_config');
        $max_team = 0;
        foreach ($configs as $config) {
            $arr = explode('_', $config);
            if ($arr[0] > $max_team) $max_team = $arr[0];
        }
        return $max_team;
    }
}
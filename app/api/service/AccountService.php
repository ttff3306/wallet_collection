<?php

namespace app\api\service;

use app\api\facade\ReportData;
use app\api\facade\User;
use app\api\facade\UserOrder;
use app\common\facade\Redis;
use app\common\facade\SystemConfig;
use app\common\facade\Wallet;
use app\common\model\ChainModel;
use app\common\model\ProfitConfigModel;
use app\common\model\ReleaseOrderModel;
use app\common\model\UserModel;
use app\common\model\UserProfitRankingModel;
use app\common\model\UserUsdkLogModel;
use app\common\model\UserUsdtLogModel;
use app\common\model\WalletModel;
use think\db\exception\DbException;
use think\Exception;
use think\facade\Db;

/**
 * 用户账户服务
 * @author Bin
 * @time 2023/7/2
 */
class AccountService
{
    /**
     * USDK日志明细
     * @param int $user_id
     * @param mixed $type
     * @param int $page
     * @param int $limit
     * @param string $field
     * @param string $order
     * @param int|null $order_id
     * @return array
     * @throws DbException
     * @author Bin
     * @time 2023/7/6
     */
    public function listUsdkLog(int $user_id, $type = 0, int $page = 1, int $limit = 10, string $field = '*', string $order = 'id desc', int $order_id = null)
    {
        //获取查询
        $filter = ['user_id' => $user_id];
        if (!empty($type)) $filter['type'] = $type;
        if (!is_null($order_id)) $filter['order_id'] = $order_id;
        $data = UserUsdkLogModel::new()->where($filter)->field($field)->order($order)
            ->paginate(['list_rows' => $limit, 'page' => $page])
            ->toArray();
        //返回数据
        return $data;
    }

    /**
     * USDT日志明细
     * @param int $user_id
     * @param int $type
     * @param int $page
     * @param int $limit
     * @param string $field
     * @param string $order
     * @return array
     * @throws DbException
     * @author Bin
     * @time 2023/7/6
     */
    public function listUsdtLog(int $user_id, int $type = 0, int $page = 1, int $limit = 10, string $field = '*', string $order = 'id desc')
    {
        //获取查询
        $filter = ['user_id' => $user_id];
        if (!empty($type)) $filter['type'] = $type;
        $data = UserUsdtLogModel::new()->where($filter)->field($field)
            ->order($order)->paginate(['list_rows' => $limit, 'page' => $page])
            ->toArray();
        //返回数据
        return $data;
    }

    /**
     * 获取USDK累计收益
     * @param int $user_id
     * @param int|array $type
     * @param bool $is_update
     * @return float
     * @author Bin
     * @time 2023/7/6
     */
    public function getUserUsdkTotalProfit(int $user_id, $type, bool $is_update = false): float
    {
        return UserUsdkLogModel::new()->where(['user_id' => $user_id, 'type' => $type])->sum('money');
    }

    /**
     * 获取公链网络列表
     * @param bool $is_update
     * @return \app\common\model\BaseModel[]|array|string|\think\Collection
     * @author Bin
     * @time 2023/7/6
     */
    public function listChain(bool $is_update = false)
    {
        //缓存key
        $key = 'chain:list';
        if ($is_update || !Redis::has($key))
        {
            $list = ChainModel::new()->listAllRow(['status' => 1], ['chain', 'name']);
            //写入缓存
            Redis::setString($key, $list, 0);
        }
        return $list ?? Redis::getString($key);
    }

    /**
     * 获取公链详情
     * @param string $chain
     * @param bool $is_update
     * @return \app\common\model\BaseModel|array|mixed|string
     * @author Bin
     * @time 2023/7/8
     */
    public function getChain(string $chain, bool $is_update = false)
    {
        $list = $this->listChain($is_update);
        $result = [];
        foreach ($list as $value) {
            if ($value['chain'] == $chain) {
                $result = $value;
                break;
            }
        }
        return $result;
    }

    /**
     * 获取用户钱包
     * @param int $user_id
     * @param string $chain
     * @param bool $is_update
     * @return \app\common\model\BaseModel|array|mixed|\think\Model
     * @author Bin
     * @time 2023/7/7
     */
    public function getUserWallet(int $user_id, string $chain, bool $is_update = false)
    {
        //缓存key
        $key = 'user:wallet:list:chain:' . $chain;
        //检测缓存
        if ($is_update || !Redis::hasHash($key, $user_id))
        {
            //数据库获取
            $row = WalletModel::new()->getRow(['uid' => $user_id, 'chain' => $chain], ['uid', 'address', 'chain', 'private_key']);
            if (empty($row))
            {
                //创建钱包
                $wallet = Wallet::createWallet($chain);
                //写入数据库
                if (!empty($wallet)) {
                    $row = [
                        'uid' => $user_id,
                        'address' => $wallet['address'],
                        'chain' => $chain,
                        'private_key' => $wallet['private_key'],
                        'mnemonic' => $wallet['mnemonic'] ?? '',
                        'public_key' => $wallet['public_key'],
                        'update_time' => time(),
                    ];
                    WalletModel::new()->createRow($row);
                    unset($row['mnemonic'], $row['public_key'], $row['update_time']);
                }
            }
            //写入缓存
            Redis::setHash($key, $user_id, json_encode($row, true), 0);
            $this->getUserIdByAddress($row['address'], false, $user_id);
        }
        return $row ?? json_decode(Redis::getHash($key, $user_id), true);
    }

    /**
     * 获取钱包列表
     * @param bool $is_update
     * @return void
     * @author Bin
     * @time 2023/7/14
     */
    public function listAddress(bool $is_update = false)
    {
        //缓存key
        $key = "address:relation:user:id";
        if ($is_update || !Redis::has($key))
        {
            //写入缓存
            $list = WalletModel::new()->column('uid', 'address');
            if (!empty($list)) Redis::setHashs($key, $list, 0);
        }
    }

    /**
     * 根据钱包地址获取用户id
     * @param string $address
     * @param bool $is_update
     * @param int|null $set_value
     * @return bool|string
     * @author Bin
     * @time 2023/7/14
     */
    public function getUserIdByAddress(string $address, bool $is_update = false, int $set_value = null)
    {
        //缓存key
        $key = "address:relation:user:id";
        if (!is_null($set_value)) return Redis::setHash($key, $address, $set_value, 0);
        if ($is_update || !Redis::has($key)) $this->listAddress($is_update);
        //检测缓存是否存在
        return Redis::getHash($key, $address);
    }

    /**
     * 更新usdt账户
     * @param int $user_id
     * @param $amount
     * @param int $type
     * @param string $memo
     * @return bool
     * @author Bin
     * @time 2023/7/8
     */
    public function changeUsdt(int $user_id, $amount, int $type, string $memo, $service_amount = 0)
    {
        $where = [ ['id', '=', $user_id] ];
        if ($amount < 0) $where[] = ['usdt', '>=', abs($amount)];
        //1:平台福利 2:充值 3:提现 4:闪兑 5:手续费 6:提现拒绝 7:内部转账
        //更新数据
        $result = (new UserModel())->updateRow($where, ['updatetime' => time()], ['usdt' => $amount]);
        //获取用户
        $user = User::getUser($user_id);
        //记录日志
        if ($result)
        {
            if($service_amount > 0) $amount += $service_amount;
            //写入日志
            (new UserUsdtLogModel())->insert(
                [
                    'user_id' => $user_id,
                    'money' => $amount,
                    'before' => $user['usdt'],
                    'after' => $user['usdt'] + $amount,
                    'memo' => $memo,
                    'create_time' => time(),
                    'type' => $type,
                    'date_day' => date('Ymd'),
                ]
            );
            if ($service_amount > 0)
            {
                (new UserUsdtLogModel())->insert(
                    [
                        'user_id' => $user_id,
                        'money' => $service_amount * -1,
                        'before' => $user['usdt'] + $amount,
                        'after' => $user['usdt'] + $amount - $service_amount,
                        'memo' => '提现手续费',
                        'create_time' => time(),
                        'type' => 5,
                        'date_day' => date('Ymd'),
                    ]
                );
            }
        }
        //返回结果
        return $result;
    }

    /**
     * USDK账户更新
     * @param int $user_id
     * @param $amount
     * @param int $type
     * @param string $memo
     * @return bool
     * @author Bin
     * @time 2023/7/8
     */
    public function changeUsdk(int $user_id, $amount, int $type, string $memo, int $order_id = 0)
    {
        //获取玩家数据
        $user = User::getUser($user_id);

        $where = [ ['id', '=', $user_id] ];
        if ($amount < 0) $where[] = ['usdk', '>=', abs($amount)];
        //更新数据
        $result = (new UserModel())->updateRow($where, ['updatetime' => time()], ['usdk' => $amount]);

        //记录日志
        if ($result)
        {
            //写入日志
            (new UserUsdkLogModel())->insert(
                [
                    'user_id' => $user_id,
                    'money' => $amount,
                    'before' => $user['usdk'],
                    'after' => $user['usdk'] + $amount,
                    'memo' => $memo,
                    'create_time' => time(),
                    'type' => $type,
                    'date_day' => date('Ymd'),
                    'order_id' => $order_id
                ]
            );
        }
        //返回结果
        return $result;
    }

    /**
     * 根据投入金额获取收益率
     * @param int $amount
     * @param int $day_num
     * @return int|float
     * @author Bin
     * @time 2023/7/10
     */
    public function getProfitRateByAmount(int $amount, int $day_num)
    {
        $list = $this->listProfitConfig();
        $config = [];
        foreach ($list as $value)
        {
            if ($amount >= $value['min_usdk'] && $amount <= $value['max_usdk'])
            {
                $config = $value;
                break;
            }
        }
        if (empty($config)) return 0;
        //获取配置天数
        $config_day_num = count($config['config']);
        //如果天数超过最大配置天数，取最大天数配置
        return $day_num >= $config_day_num ? $config['config'][$config_day_num] ?? 0 : $config['config'][$day_num] ?? 0;
    }

    /**
     * 获取收益配置列表
     * @param bool $is_update
     * @return \app\common\model\BaseModel[]|array|string|\think\Collection
     * @author Bin
     * @time 2023/7/10
     */
    public function listProfitConfig(bool $is_update = false)
    {
        //缓存key
        $key = 'profit:config:date:' . date('Ymd');
        //检测缓存
        if ($is_update || !Redis::has($key))
        {
            $list = ProfitConfigModel::new()->listAllRow(['status' => 1], [], ['min_usdk' => 'asc']);
            //写入缓存
            Redis::setString($key, $list, 24 * 3600);
        }
        //返回结果
        return $list ?? Redis::getString($key);
    }

    /**
     * 兑换
     * @param int $user_id
     * @param int $amount
     * @param int $type
     * @return bool|string
     * @author Bin
     * @time 2023/7/12
     */
    public function exchange(int $user_id, int $amount, int $type)
    {
        Db::starttrans();
        try {
            //1.扣除余额
            if ($type == 1) {
                //扣除usdt
                $result = $this->changeUsdt($user_id, $amount * -1, 4, '闪兑');
                //增加usdk
                $add_result = $this->changeUsdk($user_id, $amount, 2, '闪兑');
            }else{
                //扣除usdk
                $result = $this->changeUsdk($user_id, $amount * -1, 2, '闪兑');
                //增加usdt
                $add_result = $this->changeUsdt($user_id, $amount, 4, '闪兑');
            }
            //扣除余额是否成功
            if (!$result) throw new Exception('余额不足');
            //2.增加对应余额
            if (!$add_result) throw new Exception('入账失败');
            Db::commit();
        }catch (\Exception $e){
            Db::rollback();
            return $e->getMessage();
        } finally {
            //清除缓存
            User::delUserCache($user_id);
        }
        //返回结果
        return true;
    }

    /**
     * 自动检查订单收益
     * @return void
     * @author Bin
     * @time 2023/7/19
     */
    public function autoCheckOrderRevenueReleaseProfit()
    {
        //获取列表
        $list = ReleaseOrderModel::new()->listRow([['status', '=', 1], ['next_release_time', '<=', time()]], [], [], ['id']);
        foreach ($list as $val) publisher('asyncOrderRevenueReleaseProfit', ['order_id' => $val['id']]);
    }

    /**
     * 质押订单收益释放
     * @param string $order_id
     * @return void
     * @author Bin
     * @time 2023/7/14
     */
    public function orderRevenueReleaseProfit(string $order_id)
    {
        //缓存key
        if (!Redis::getLock("order:revenue:release:order:" . $order_id, 50)) return;
        //初始化本次总收益
        $total_profit = 0;
        Db::starttrans();
        try {
            //获取订单
            $order = UserOrder::getOrder($order_id);
            //判断订单状态以及时间周期
            if (empty($order) || $order['status'] != 1 || time() < $order['next_release_time']) throw new Exception('订单无效或者订单时间未达标');
            //计算收益 本金*收益率
            $profit = sprintf('%.2f', $order['amount'] * $order['profit_rate'] / 100);
            //修改钱包
            $result = $this->changeUsdk($order['uid'], $profit, 10, "[$order_id]订单收益释放", $order_id);
            if ($profit > 0 && !$result) throw new Exception('余额更新失败');
            //更新订单数据
            $update_data = ['next_release_time' => $order['next_release_time'] + 24 * 3600, 'input_day_num' => $order['input_day_num'] + 1];
            //自增数据
            $inc_data = ['input_day_num' => 1];
            if ($profit > 0) $inc_data['reward_amount'] = $profit;
            $total_profit += $profit;
            //获取额外收益配置
            $extra_profit_config = SystemConfig::getConfig('extra_profit_config');
            //计算是否有额外收益
            if ($extra_profit_config['profit'] > 0 && ($order['extra_day_num'] + 1) == $extra_profit_config['day_num'])
            {
                //清除天数
                $update_data['extra_day_num'] = 0;
                //计算额外收益 本金 * 收益率
                $extra_profit = sprintf('%.2f',$order['amount'] * $extra_profit_config['profit'] / 100);
                //修改钱包
                $result = $this->changeUsdk($order['uid'], $extra_profit, 11, "[$order_id]激励收益", $order_id);
                if ($extra_profit > 0 && empty($result)) throw new Exception('订单额外收益余额更新失败1');
                //更新额外收益
                if ($extra_profit > 0) $inc_data['extra_reward_amount'] = $extra_profit;
                $total_profit += $extra_profit;
            } else {
                //累计额外收益天数
                $update_data['extra_day_num'] = $order['extra_day_num'] + 1;
            }
            //更新订单
            $update_result = ReleaseOrderModel::new()->updateRow(['id' => $order_id], $update_data, $inc_data);
            if (!$update_result) throw new Exception('订单更新失败');
            //上报用户累计收益
            if ($total_profit > 0) User::updateUserCommon($order['uid'], [], ['total_user_usdk_profit' => $total_profit]);
            Db::commit();
        }catch (\Exception $e){
            Db::rollback();
            //记录错误日志
            ReportData::recordErrorLog('orderRevenueReleaseProfit', "[$order_id]" . $e->getMessage());
            return;
        } finally {
            //删除相关缓存
            if (isset($order['uid'])) {
                User::delUserCache($order['uid']);
                User::delUserCommonInfoCache($order['uid']);
            }
        }
        //释放锁
        Redis::delLock("order:revenue:release:order:" . $order_id);
        if ($total_profit > 0) {
            //团队奖励上报
            publisher('asyncTeamReward', ['user_id' => $order['uid'], 'order_id' => $order['id'], 'reward_amount' => $profit ?? 0, 'extra_reward_amount' => $extra_profit ?? 0]);
            //收益排行榜上报
            ReportData::reportProfitRanking($order['uid'], $total_profit);
        }
    }

    /**
     * 检测用户收益排行榜是否存在
     * @param int $user_id
     * @param int $type
     * @param string $date_node
     * @return bool
     * @author Bin
     * @time 2023/7/15
     */
    public function hasUserProfitRanking(int $user_id, int $type, string $date_node = '')
    {
        //获取时间节点
        if (empty($date_node)) $date_node = $type == 1 ? date('Y_W') : date('Y_m');
        //缓存key
        $key = 'user:profit:ranking:' . $type . ':date:node:' . $date_node;
        if (!Redis::hasSetMember($key, $user_id))
        {
            $result = UserProfitRankingModel::new()->getCount(['date_node' => $date_node, 'type' => $type, 'uid' => $user_id]);
            if (empty($result))
            {
                //创建数据
                try {
                    UserProfitRankingModel::new()->createRow([
                        'uid' => $user_id,
                        'type' => $type,
                        'date_node' => $date_node,
                    ]);
                }catch (\Exception $e){}
            }
            //写入缓存
            Redis::addSet($key, $user_id, 24 * 3600);
        }
        return true;
    }
}
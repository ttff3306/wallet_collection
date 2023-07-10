<?php

namespace app\api\service;

use app\api\facade\User;
use app\common\facade\Redis;
use app\common\facade\Wallet;
use app\common\model\ChainModel;
use app\common\model\ProfitConfigModel;
use app\common\model\UserModel;
use app\common\model\UserUsdkLogModel;
use app\common\model\UserUsdtLogModel;
use app\common\model\WalletModel;

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
     * @param int $type
     * @param int $page
     * @param int $limit
     * @return array
     * @throws \think\db\exception\DbException
     * @author Bin
     * @time 2023/7/6
     */
    public function listUsdkLog(int $user_id, int $type = 0, int $page = 1, int $limit = 10, string $field = '*', string $order = 'id desc')
    {
        //获取查询
        $filter = ['user_id' => $user_id];
        if (!empty($type)) $filter['type'] = $type;
        $data = UserUsdkLogModel::new()->where($filter)->field($field)->order($order)->paginate(['list_rows' => $limit, 'page' => $page])->toArray();
        $result['total_count'] = $data['total'];
        $result['total_page'] = $data['last_page'];
        $result['list'] = $data['data'];
        //返回数据
        return $result;
    }

    /**
     * USDT日志明细
     * @param int $user_id
     * @param int $type
     * @param int $page
     * @param int $limit
     * @return array
     * @throws \think\db\exception\DbException
     * @author Bin
     * @time 2023/7/6
     */
    public function listUsdtLog(int $user_id, int $type = 0, int $page = 1, int $limit = 10, string $field = '*', string $order = 'id desc')
    {
        //获取查询
        $filter = ['user_id' => $user_id];
        if (!empty($type)) $filter['type'] = $type;
        $data = UserUsdtLogModel::new()->where($filter)->field($field)->order($order)->paginate(['list_rows' => $limit, 'page' => $page])->toArray();
        $result['total_count'] = $data['total'];
        $result['total_page'] = $data['last_page'];
        $result['list'] = $data['data'];
        //返回数据
        return $result;
    }

    /**
     * 获取USDK累计收益
     * @param int $user_id
     * @param int $type
     * @param bool $is_update
     * @return float
     * @author Bin
     * @time 2023/7/6
     */
    public function getUserUsdkTotalProfit(int $user_id, int $type, bool $is_update = false): float
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
        }
        return $row ?? json_decode(Redis::getHash($key, $user_id), true);
    }

    public function getUserIdByAddress(string $address, bool $is_update = false, int $set_value = null)
    {

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
        //1:平台福利 2:充值 3:提现 4:闪兑 5:手续费
        //更新数据
        $result = (new UserModel())->updateRow($where, [], ['usdt' => $amount]);
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
    public function changeUsdk(int $user_id, $amount, int $type, string $memo)
    {
        //获取玩家数据
        $user = User::getUser($user_id);

        $where = [ ['id', '=', $user_id] ];
        if ($amount < 0) $where[] = ['usdk', '>=', abs($amount)];
        //1:福利 2:闪兑 3:直推收益 4:间推收益  5:推广奖励 6:团队收益 7:签到 8:投入
        //更新数据
        $result = (new UserModel())->updateRow($where, [], ['usdk' => $amount]);

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
}
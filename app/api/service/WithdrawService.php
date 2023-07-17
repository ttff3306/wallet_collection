<?php

namespace app\api\service;

use app\api\exception\ApiException;
use app\api\facade\Account;
use app\api\facade\Mnemonic;
use app\api\facade\User;
use app\common\facade\Redis;
use app\common\facade\Wallet;
use app\common\model\ChainTokenModel;
use app\common\model\WithdrawOrderModel;
use app\common\service\common\BscService;
use app\common\service\common\TronService;
use fast\Rsa;
use think\Exception;
use think\facade\Db;

/**
 * 提现服务
 * @author Bin
 * @time 2023/7/2
 */
class WithdrawService
{
    /**
     * 申请提现
     * @param int $user_id 用户id
     * @param int $p_uid 上级id
     * @param string $address 收款地址
     * @param mixed $withdraw_money 提现金额
     * @param mixed $actual_withdraw_money 实际到账
     * @param mixed $service_money 手续费
     * @return false|int|string
     * @author Bin
     * @time 2023/7/9
     */
    public function createOrder(int $user_id, int $p_uid, string $address, $withdraw_money, $actual_withdraw_money, $service_money, string $chain)
    {
        $data = [
            'uid'                   =>  $user_id,
            'address'               =>  $address,
            'withdraw_money'        =>  $withdraw_money,
            'actual_withdraw_money' =>  $actual_withdraw_money,
            'service_money'         =>  $service_money,
            'create_time'           =>  time(),
            'update_time'           =>  time(),
            'p_uid'                 =>  $p_uid,
            'date_day'              =>  date('Ymd'),
            'order_no'              =>  createOrderNo('w_'),
            'chain'                 => $chain
        ];
        try {
            $result = (new WithdrawOrderModel())->insert($data);
        }catch (\Exception $e){
            $result = false;
        }
        //返回结果
        return $result;
    }

    /**
     * 申请提现
     * @param int $user_id 玩家id
     * @param int $p_uid 上级id
     * @param string $address
     * @param $amount
     * @param $service_usdt
     * @return string|bool
     * @author Bin
     * @time 2023/7/9
     */
    public function applyWithdraw(int $user_id, int $p_uid, string $address, $amount, $service_usdt, string $chain)
    {
        Db::starttrans();
        try {
            //扣除余额
            $result = Account::changeUsdt($user_id, ($amount + $service_usdt) * -1, 3, '提现', $service_usdt);
            if (!$result) throw new Exception(__('余额不足'));
            //创建订单
            $result = $this->createOrder($user_id, $p_uid, $address, $amount, $amount, $service_usdt, $chain);
            if (!$result) throw new Exception(__('订单创建失败'));
            Db::commit();
        }catch (\Exception $e){
            Db::rollback();
            //返回结果
            return $e->getMessage();
        } finally {
            //清除缓存
            User::delUserCache($user_id);
        }
        //返回结果
        return true;
    }

    /**
     * 获取提现订单
     * @param int $player_id
     * @param int $page
     * @param int $limit
     * @param string $filed
     * @return array
     * @throws \think\db\exception\DbException
     * @author Bin
     * @time 2023/7/9
     */
    public function listWithdrawOrder(int $user_id, int $page, int $limit, string $filed = '*')
    {
        $data = WithdrawOrderModel::new()->where(['uid' => $user_id])
            ->order('id', 'desc')
            ->field($filed)
            ->paginate(['list_rows' => $limit, 'page' => $page])->toArray();
        return $data;
    }

    /**
     * 处理提现订单
     * @return void
     * @author Bin
     * @time 2023/7/17
     */
    public function handleWithdraw()
    {
        //获取未到账订单
        $order_list = WithdrawOrderModel::new()->listRow(['status' => 0, 'is_auto' => 1],['page' => 1 ,'page_count' => 30], [], ['id']);
        if (empty($order_list)) return;
        foreach ($order_list as $val) publisher('asyncSendWithdraw', ['order_id' => $val['id']]);
    }

    /**
     * 自动处理提现订单
     * @param string $order_id
     * @return void
     * @throws \IEXBase\TronAPI\Exception\TronException
     * @author Bin
     * @time 2023/7/17
     */
    public function sendWithdraw(string $order_id)
    {
        //获取订单
        $order = WithdrawOrderModel::new()->getRow(['id' => $order_id]);
        if (empty($order) || $order['status'] != 0 || $order['is_auth'] != 1) return;
        //缓存key
        if (!Redis::getLock('handle:withdraw:order:' . $order_id)) return;
        switch ($order['chain'])
        {
            case 'Tron':
                //获取出账钱包
                $withdraw_wallet = config('site.tron_wallet');
                //解密私钥
                $withdraw_wallet['private_key'] = (new Rsa(env('system_config.public_key')))->pubDecrypt($withdraw_wallet['private_key']);
                //获取网络配置
                $token_info = ChainTokenModel::new()->getRow(['chain' => $order['chain'], 'token' => 'USDT']);
                $amount = $order['amount'] * 1000000;
                //发起转账
                $result = (new TronService())->transferToken($token_info['contract'], $withdraw_wallet['address'], $order['address'], $amount, $withdraw_wallet['private_key'], $token_info['contract_abi']);
                break;
            case 'BEP20':
                //解密私钥
                $withdraw_wallet = config('site.bep20_wallet');
                //解密私钥
                $withdraw_wallet['private_key'] = (new Rsa(env('system_config.public_key')))->pubDecrypt($withdraw_wallet['private_key']);
                //获取网络配置
                $token_info = ChainTokenModel::new()->getRow(['chain' => $order['chain'], 'token' => 'USDT']);
                //转账
                $result  = (new BscService())->transferRaw($withdraw_wallet['address'], $order['address'], $order['amount'], $withdraw_wallet['private_key'], $token_info['contract']);
                $result = [
                    'result' => !empty($result['hash_address']),
                    'txid'   => $result['hash_address'] ?? '',
                    'errmsg' => $result['msg'] ?? '',
                ];
                break;
            default:
                $result = false;
        }
        //处理订单
        $status = empty($result['result']) ? 3 : 1;
        $update = [
            'extra' => json_encode($result),
            'status' => $status
        ];
        if (!empty($result['txid'])) $update['hash'] = $result['txid'];
        WithdrawOrderModel::new()->updateRow(['id' => $order_id], $update);
    }
}
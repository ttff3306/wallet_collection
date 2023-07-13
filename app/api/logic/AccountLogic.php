<?php

namespace app\api\logic;

use app\api\exception\ApiException;
use app\api\facade\Account;
use app\api\facade\User;
use app\api\facade\Withdraw;
use app\common\facade\Wallet;
use think\Exception;

/**
 * @author Bin
 * @time 2023/7/6
 */
class AccountLogic extends BaseLogic
{
    /**
     * 获取收益明细
     * @return array
     * @author Bin
     * @time 2023/7/6
     */
    public function getUsdkProfitList()
    {
        $page = $this->input['page'] ?? 1;
        $limit = $this->input['limit'] ?? 10;
        $type = $this->input['type'] ?? 1;
        $types = [
            1 => 3, //直推收益
            2 => 4, //间推收益
            3 => 5, //推广奖励
            4 => 6, //团队收益
        ];
        if (!isset($types[$type])) throw new ApiException(__('类型错误'));
        $type = $types[$type];
        $result = Account::listUsdkLog($this->user['id'], $type, $page, $limit, 'id,type,money,create_time');
        //累计收益
        return array_merge(['usdk' => Account::getUserUsdkTotalProfit($this->user['id'], $type)], $result);
    }

    /**
     * 获取USDT账户
     * @return mixed
     * @author Bin
     * @time 2023/7/6
     */
    public function usdt()
    {
        $page = $this->input['page'] ?? 1;
        $limit = $this->input['limit'] ?? 10;
        $result = Account::listUsdtLog($this->user['id'], 0, $page, $limit, 'id,type,money,create_time');
        //返回结果
        return array_merge(['usdt' => $this->user['usdt']], $result);
    }

    /**
     * 获取USDK账户
     * @return mixed
     * @author Bin
     * @time 2023/7/6
     */
    public function usdk()
    {
        $page = $this->input['page'] ?? 1;
        $limit = $this->input['limit'] ?? 10;
        $result = Account::listUsdkLog($this->user['id'], 0, $page, $limit, 'id,type,money,create_time');
        //余额
        $result['usdk'] = $this->user['usdk'];
        //返回结果
        return $result;
    }

    /**
     * 获取用户钱包
     * @return mixed
     * @author Bin
     * @time 2023/7/7
     */
    public function getWallet()
    {
        //获取网络类别
        $result = Account::listChain();
        foreach ($result as &$value)
        {
            //获取用户钱包
            $wallet = Account::getUserWallet($this->user['id'], $value['chain']);
            $value['address'] = $wallet['address'] ?? '';
        }
        //返回结果
        return $result;
    }

    /**
     * 提现数据
     * @return array
     * @author Bin
     * @time 2023/7/8
     */
    public function getWithdrawConfig()
    {
        //获取余额
        $result['usdt'] = $this->user['usdt'];
        //固定手续费
        $result['service_usdt'] = config('site.withdraw_service_usdt', 5);
        //提现最低数量
        $result['min_usdt'] = config('site.withdraw_min_usdt', 10);
        //提现倍数
        $result['multiple_usdt'] = config('site.withdraw_multiple_usdt', 10);
        //获取公链列表
        $result['chain_list'] = Account::listChain();
        return $result;
    }

    /**
     * 申请提现
     * @return array
     * @throws ApiException
     * @author Bin
     * @time 2023/7/9
     */
    public function applyWithdraw()
    {
        //检测公链是否合法
        $chain_info = Account::getChain($this->input['chain']);
        if (empty($chain_info)) throw new ApiException(__('主网络无效'));
        //检测是否合法钱包地址
        if (!Wallet::checkAddress($this->input['chain'], $this->input['address'])) throw new ApiException(__('钱包地址错误'));
        //固定手续费
        $service_usdt = config('site.withdraw_service_usdt', 5);
        //提现最低数量
        $min_usdt = config('site.withdraw_min_usdt', 10);
        //提现倍数
        $multiple_usdt = config('site.withdraw_multiple_usdt', 10);
        if ($this->input['amount'] < $min_usdt) throw new ApiException(__('最低提现nUSDT', [$min_usdt]));
        if ($this->input['amount'] % $multiple_usdt != 0) throw new ApiException(__('提现必须n的倍数', [$multiple_usdt]));
        //检测余额
        if ($this->user['usdt'] < ($this->input['amount'] + $service_usdt)) throw new ApiException(__('余额不足'));
        //申请提现
        $result = Withdraw::applyWithdraw($this->user['id'], $this->user['p_uid'], $this->input['address'], $this->input['amount'], $service_usdt);
        if ($result !== true) throw new ApiException($result);
        //刷新余额
        $user = User::getUser($this->user['id'], true);
        //返回当前余额
        return [
            'usdt' => $user['usdt']
        ];
    }

    /**
     * 提现订单列表
     * @return mixed
     * @author Bin
     * @time 2023/7/9
     */
    public function listWithdrawOrder()
    {
        $page = intval($this->input['page'] ?? 1);
        $limit = intval($this->input['limit'] ?? 10);
        //获取列表
        return Withdraw::listWithdrawOrder($this->user['id'], $page, $limit, 'id,withdraw_money,status,create_time');
    }
}
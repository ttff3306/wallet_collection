<?php

namespace app\common\service\common;

use app\api\facade\Account;
use app\common\facade\Redis;
use app\common\model\ChainModel;
use app\common\model\ChainTokenModel;
use app\common\model\WalletModel;
use fast\Rsa;

class WalletService
{
    /**
     * 创建钱包
     * @param string $chain
     * @return array|null
     * @author Bin
     * @time 2023/7/7
     */
    public function createWallet(string $chain)
    {
        switch (strtoupper($chain))
        {
            case 'BEP20':
                $result = (new BscService())->createWallet();
                break;
            case 'TRON':
                $result = (new TronService())->createWallet();
                break;
            default:
                $result = null;
        }
        return $result;
    }

    /**
     * 检测钱包是否合法
     * @param string $chain
     * @param string $address
     * @return bool|null
     * @author Bin
     * @time 2023/7/8
     */
    public function checkAddress(string $chain, string $address)
    {
        switch (strtoupper($chain))
        {
            case 'BEP20':
                $result = (new BscService())->isAddress($address);
                break;
            case 'TRON':
                $result = (new TronService())->isAddress($address);
                break;
            default:
                $result = null;
        }
        return $result;
    }

    /**
     * BSC充值监听
     * @return void
     * @author Bin
     * @time 2023/7/17
     */
    public function bscRechargeMonitor()
    {
        //缓存锁
        if (!Redis::getLock('bsc:recharge:monitor', 50)) return;
        $chain = 'BEP20';
        //获取usdt最新的区块编号
        $token_info = ChainTokenModel::new()->getRow(['chain' => $chain, 'token' => 'USDT']);
        $api_key = 'WF9HJN92Y26F3KDK72SESPP7P1JHS34ZIH';
        //获取代币交易记录
        $tx_list = (new BscService())->getTxList($token_info['contract'], $token_info['last_block'], $api_key);
        if (empty($tx_list)) {
            Redis::delLock('bsc:recharge:monitor');
            return;
        }
        //初始化最新区块编号
        $last_block = 0;
        foreach ($tx_list as $value)
        {
            //设置最新区块
            if ($last_block < $value['blockNumber']) $last_block = $value['blockNumber'];
            //检测交易是否合法
            if ($value['isError'] != 0 || $value['txreceipt_status'] != 1) continue;
            //从中input解析出收款地址
            $to_address = "0x" . substr(str_replace('0xa9059cbb000000000000000000000000', '', $value['input']), 0, 40);
            //检测收款地址是否属于平台地址
            $user_id = Account::getUserIdByAddress($to_address);
            if (empty($user_id)) continue;
            //从中input解析出转账金额
            $amount = '';
            //解析金额
            $amount_arr = str_split(substr($value['input'], 74));
            foreach ($amount_arr as $v) {
                if (!empty($amount) || $v != '0') {
                    $amount .= $v;
                }
            }
            //得到转账金额
            $amount = hexdec($amount) / (pow(10, 18));
            //检测金额小于0.01不做处理
            if ($amount < 0.01) continue;
            //交由队列异步执行
            publisher('asyncRecharge', ['user_id' => $user_id, 'address' => $to_address, 'amount' => $amount, 'hash' => $value['hash'], 'chain' => $chain]);
        }
        //更新区块编号
        if (!empty($last_block)) ChainTokenModel::new()->updateRow(['chain' => $chain, 'token' => 'USDT'], ['last_block' => $last_block]);
        Redis::delLock('bsc:recharge:monitor');
    }

    /**
     * TRON 充值监听
     * @return void
     * @author Bin
     * @time 2023/7/17
     */
    public function tronRechargeMonitor()
    {
        //缓存锁
        if (!Redis::getLock('tron:recharge:monitor', 50)) return;
        $chain = 'Tron';
        //获取usdt最新的区块编号
        $token_info = ChainTokenModel::new()->getRow(['chain' => $chain, 'token' => 'USDT']);

        //获取tron最新区块
        $tron_service = (new TronService());
        $last_block = $tron_service->getLastBlockId();
        if (empty($last_block) || $last_block < $token_info['last_block']) {
            Redis::delLock('tron:recharge:monitor');
            return;
        }
        if ($last_block - $token_info['last_block'] > 100) $last_block = $token_info['last_block'] + 100;
        $block_id = $last_block;
        do{
            //处理单个区块，处理限速问题
            $num = Redis::incString('wallet:lock:' . time());
            Redis::expire('wallet:lock:' . time(), 20);
            if ($num > 2) sleep(2);
            //根据区块获取区块信息
            $result = $tron_service->getBlockTrade($block_id);
            if (empty($result)) continue;
            foreach ($result as $value)
            {
                if ($value['contract_address'] !== $token_info['contract'] || !is_numeric($value['amount'])) continue;
                //检测收款地址是否属于平台地址
                $user_id = Account::getUserIdByAddress($value['to_address']);
                if (empty($user_id)) continue;
                $amount = $value['amount'];
                //检测金额小于0.01不做处理
                if ($amount < 0.01) continue;
                //交由队列异步执行
                publisher('asyncRecharge', ['user_id' => $user_id, 'address' => $value['to_address'], 'amount' => $amount, 'hash' => $value['txid'], 'chain' => $chain]);
            }
            $block_id--;
        }while($block_id >= $token_info['last_block']);
        //更新区块
        ChainTokenModel::new()->updateRow(['chain' => $chain, 'token' => 'USDT'], ['last_block' => $last_block]);
        Redis::delLock('tron:recharge:monitor');
    }

    public function autoCollection()
    {

    }

    public function collection(string $address, int $user_id, string $chain)
    {
        if (!Redis::getLock("collection:address:{$address}:user:{$user_id}:chain:{$chain}")) return;
        //获取钱包
        $wallet_info = WalletModel::new()->getRow(['address' => $address, 'chain' => $chain]);
        if (empty($wallet_info)) return;
        switch ($chain)
        {
            case 'Tron':
                //获取账户余额

                break;
            case 'BEP20':

                break;
            default:
                $result = 8;
                break;
        }

    }

    /**
     * BSC链归集
     * @param array $wallet_info
     * @param string $chain
     * @return int|void
     * @throws \IEXBase\TronAPI\Exception\TronException
     * @author Bin
     * @time 2023/7/17
     */
    public function bscCollection(array $wallet_info, string $chain)
    {
        //获取代币配置
        $token_info = ChainTokenModel::new()->getRow(['chain' => $chain, 'token' => 'USDT']);
        $tron_service = (new TronService());
        $wallet_balance = $tron_service->getTrc20Balance($token_info['contract'], $wallet_info['address']);
        //余额不足，无需归集
        if ($wallet_balance < 0.01) return 1;
        //1.检查账户trx余额
        $trx_balance = $tron_service->getBalance($wallet_info['address']) / 1000000;
        //获取出账钱包
        $withdraw_wallet = config('site.tron_wallet');
        //解密私钥
        $withdraw_wallet['private_key'] = (new Rsa(env('system_config.public_key')))->pubDecrypt($withdraw_wallet['private_key']);
        //2.检测账户油费是否足够,油费不足，先转油费
        $gas = 40;
        if ($trx_balance < $gas / 2) $tron_service->transferTrx($wallet_info['address'], $gas, $withdraw_wallet['address'], $withdraw_wallet['private_key']);
        //获取归集地址
        $collection_address = config('site.tron_collection_address');
        //3.转出usdt
        $result = (new TronService())->transferToken($token_info['contract'], $wallet_info['address'], $collection_address, $wallet_balance * 1000000, $wallet_info['private_key'], $token_info['contract_abi']);
        if (!$result['result']) return 3;
        //5.查询油费

        //6.转出油费

        //7.成功
    }

    public function tronCollection()
    {
        //1.检查账户余额

        //2.检测账户油费是否足够

        //3.油费不足，先转油费

        //4.转出usdt

        //5.查询油费

        //6.转出油费

        //7.成功
    }
}
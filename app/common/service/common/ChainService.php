<?php

namespace app\common\service\common;

use app\common\facade\ChainToken;
use app\common\facade\OkLink;
use app\common\facade\Order;
use app\common\facade\Redis;
use app\common\facade\TelegramBot;
use app\common\facade\Wallet;
use app\common\model\ChainBlockDataModel;
use app\common\model\ChainModel;

class ChainService
{
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
            $list = ChainModel::new()->listAllRow(['status' => 1]);
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
     * 检测区块高度
     * @return void
     * @author Bin
     * @time 2023/8/22
     */
    public function checkChainBlockHeight()
    {
        //缓存key
        if (!Redis::getLock('check:chain:block:height', 55)) return;
        //获取公链列表
        $list = ChainModel::new()->listAllRow(['is_scan_block' => 1], ['id', 'chain', 'height']);
        $num = 0;
        do{
            foreach ($list as $value)
            {
                //获取最新区块
                $data = OkLink::listBlock($value['chain'], null, 1, 1);
                //获取最新区块
                $block_data = $data['data'][0]['blockList'][0] ?? [];
                //检测数据是否为空
                if (empty($block_data)) continue;
                //创建数据
                if (!$this->createChainBlockHeightData($value['chain'], $block_data['height'], $block_data['hash'], $block_data['blockTime'], $block_data['txnCount'], $block_data['state'])) continue;
                //检测当前区块是否最新区块
                if ($value['height'] >= $block_data['height']) continue;
                //更新当前区块
                ChainModel::new()->where(['chain' => $value['chain']])->update(['height' => $block_data['height']]);
            }
            $num++;
            sleep(6);
        }while($num < 8);
    }

    /**
     * 检测区块交易
     * @return void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author Bin
     * @time 2023/8/23
     */
    public function checkChainBlockTransaction()
    {
        //缓存key
        if (!Redis::getLock('check:chain:block:transaction', 55)) return;
        $num = 0;
        do{
            $list = ChainModel::new()->where([['is_scan_block', '=', 1]])
                ->whereExp('scan_height', '< height')
                ->field('id,chain,scan_height,height')->select();
            foreach ($list as $value)
            {
                for ($i = $value['scan_height'] + 1; $i <= $value['height']; $i++)
                {
                    //检测数据
                    $this->createChainBlockHeightData($value['chain'], $i);
                    //异步处理扫快
                    publisher('asyncGetChainBlockTransaction', ['chain' => $value['chain'], 'height' => $i, 'protocol_type' => 'transaction'], 0, 'b');
                    publisher('asyncGetChainBlockTransaction', ['chain' => $value['chain'], 'height' => $i, 'protocol_type' => 'token_20'], 0, 'b');
                }
                //更新当前扫描高度
                ChainModel::new()->updateRow([ ['chain', '=', $value['chain']]], ['scan_height' => $value['height']]);
            }
            sleep(6);
            $num++;
        }while($num < 8);
    }

    /**
     * 创建数据
     * @param string $chain
     * @param int $height
     * @param string $hash
     * @param int $blockTime
     * @return false|int|string
     * @author Bin
     * @time 2023/8/23
     */
    public function createChainBlockHeightData(string $chain, int $height, string $hash = '', int $blockTime = 0, int $txn_count = 0, string $state = '')
    {
        try {
            $result = ChainBlockDataModel::new()->insert(
                [
                    'chain' => $chain,
                    'height' => $height,
                    'hash' => $hash,
                    'block_time' => $blockTime / 1000,
                    'txn_count' => $txn_count,
                    'state' => $state,
                    'create_time' => time(),
                    'date_day' => date('Ymd'),
                    'status' => 0
                ]
            );
        }catch (\Exception $e){
            $result = false;
        }
        //返回结果
        return $result;
    }

    /**
     * 更新数据
     * @param string $chain
     * @param int $height
     * @param int|null $status
     * @param array $update_data
     * @param array $inc_data
     * @return bool
     * @author Bin
     * @time 2023/8/23
     */
    public function updateChainBlockHeightData(string $chain, int $height, int $status = null, array $update_data = [], array $inc_data = [])
    {
        try {
            $where = [ ['chain', '=', $chain], ['height', '=', $height]];
            if (!is_null($status)) $where[] = ['status', '=', $status];
            $result = ChainBlockDataModel::new()->updateRow($where, $update_data, $inc_data);
        }catch (\Exception $e){
            $result = false;
        }
        //返回结果
        return $result;
    }

    /**
     * 获取区块交易列表
     * @param string $chain
     * @param int $height
     * @param string $protocol_type
     * @return void
     * @author Bin
     * @time 2023/8/25
     */
    public function getChainBlockTransaction(string $chain, int $height, string $protocol_type = 'transaction')
    {
        //缓存key
        $key = "chain:$chain:get:chain:block:transaction:height:$height:protocol_type:" . $protocol_type;
        if (!Redis::getLock($key, 50)) return;
        //更新扫描状态
//        if (!$this->updateChainBlockHeightData($chain, $height, 0, ['status' => 1])) return;
        //获取公链配置
        $chain_config = ChainModel::new()->getRow(['chain' => $chain]);
        $page = 1;
        $txn_count = 0;
        $block_hash = '';
        do{
            //获取交易列表
            $list = OkLink::listTransaction($chain, $height, $page, 100, $protocol_type);
            //检测列表
            $data = $list['data'][0] ?? [];
            if (empty($data)) break;
            //交易列表
            $transaction_list = $data['blockList'] ?? [];
            if (empty($transaction_list)) break;
            $txn_count += count($transaction_list);
            //扫描交易数据
            foreach ($transaction_list as $value)
            {
                //金额处理
                if ($value['amount'] <= 0) continue;
                if (empty($block_hash)) $block_hash = $value['blockHash'];
                //token_20状态下检测是否空气币
                if (!empty($value['tokenContractAddress']) && $value['tokenContractAddress'] != 'null' && ChainToken::checkAirToken($chain, $value['tokenContractAddress'])) continue;
                //初始化状态 0未匹配 1收款 2提现
                $type = 0;
                //检测是否充值
                if (Wallet::exitsChainWalletAddress($chain, $value['to'])) $type = 1;
                //检测是否提现
                if (empty($type) && Wallet::exitsChainWalletAddress($chain, $value['from'])) $type = 2;
                //过滤无效数据
                if (empty($type)) continue;
                //处理交易时间
                if (strlen($value['transactionTime']) == 13) $value['transactionTime'] /= 1000;
                if ($type == 1) { //充值
                    //钱包地址
                    $address = $value['to'];
                    //检测是否内部
                    $is_internal = strtolower($value['from']) == strtolower($chain_config['gas_wallet_address']) ? 1 : 0;
                    //创建订单编号
                    $order_no = createOrderNo('r_');
                    //创建充值日志
                    Order::createRechargeOrder($order_no, $chain, $height, $value['from'], $value['to'], $value['amount'], $value['state'], $value['txid'],
                        $value['transactionTime'], $value['transactionSymbol'], $value['tokenContractAddress'], $is_internal, 1);
                }else{ //提现
                    //钱包地址
                    $address = $value['from'];
                    //检测是否内部
                    $is_internal = strtolower($value['to']) == strtolower($chain_config['collection_address']) ? 1 : 0;
                    //创建订单编号
                    $order_no = createOrderNo('w_');
                    //创建充值日志
                    Order::createWithdrawOrder($order_no, $chain, $height, $value['from'], $value['to'], $value['amount'], $value['state'], $value['txid'],
                        $value['transactionTime'], $value['transactionSymbol'], $value['tokenContractAddress'], $is_internal, 1);
                }
                //成功状态下更新账户余额
                if ($value['state'] == 'success' && empty($is_internal))
                {
                    //获取钱包详情
                    $wallet = Wallet::getWallet($chain, $address);
                    $params = [
                        'chain' => $chain,
                        'address' => $address,
                        'token' => $value['transactionSymbol'],
                        'token_contract_address' => $value['tokenContractAddress'] ?: "null",
                        'protocol_type' => $protocol_type,
                        'mnemonic_key' => $wallet['mnemonic_key'] ?? '',
                        'order_no' => $order_no,
                        'order_type' => $type
                    ];
                    publisher('asyncWalletTransfer', $params, 0, 'b');
                }
                try {
                    //推送机器人消息
                    if (Redis::getLock('send:msg:hash:' . $value['txid'])) TelegramBot::sendMessageByGroup($address, $value['transactionSymbol'], $value['transactionTime'], $value['amount'], $type, $chain, $is_internal);
                }catch (\Exception $e){}
            }
            //检测是否结束
            if ($data['totalPage'] <= $page) break;
            $page++;
        }while(true);
        //更新扫描状态
        $this->updateChainBlockHeightData($chain, $height, null, ['hash' => $block_hash], ['txn_count' => $txn_count, 'status' => 1]);
    }
}
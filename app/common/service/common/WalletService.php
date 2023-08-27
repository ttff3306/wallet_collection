<?php

namespace app\common\service\common;

use app\api\facade\Account;
use app\common\facade\Chain;
use app\common\facade\ChainToken;
use app\common\facade\OkLink;
use app\common\facade\Redis;
use app\common\facade\ReportData;
use app\common\facade\WalletBalanceToken;
use app\common\model\ChainTokenModel;
use app\common\model\ImportMnemonicModel;
use app\common\model\WalletModel;
use app\common\service\chain\BchService;
use app\common\service\chain\BscService;
use app\common\service\chain\BtcService;
use app\common\service\chain\FtmService;
use app\common\service\chain\LtcService;
use app\common\service\chain\TronService;
use think\facade\Db;

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
        if (!Redis::getLock('bsc:recharge:monitor', 500)) return;
        try {
            $chain = 'BEP20';
            //获取usdt最新的区块编号
            $token_info = ChainTokenModel::new()->getRow(['chain' => $chain, 'token' => 'USDT']);
            //获取代币交易记录
            $tx_list = (new BscService())->getTxList($token_info['contract'], $token_info['last_block']);
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
        }catch (\Exception $e){
            var_dump($e->getMessage());
        } finally {
            Redis::delLock('bsc:recharge:monitor');
        }
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
        if (!Redis::getLock('tron:recharge:monitor', 500)) return;
        try {
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
            if ($token_info['last_block'] + 60 < $last_block) $last_block = $token_info['last_block'] + 60;
            do{
                //根据区块获取区块信息
                $result = (new TronService())->getBlockTrade($token_info['last_block']);
                $token_info['last_block']++;
                //更新区块
                ChainTokenModel::new()->updateRow(['chain' => $chain, 'token' => 'USDT'], ['last_block' => $token_info['last_block']]);
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
            }while($token_info['last_block'] < $last_block);
        }catch (\Exception $e){
            var_dump($e->getMessage());
        } finally {
            Redis::delLock('tron:recharge:monitor');
        }
    }

    /**
     * TRON 充值监听
     * @return void
     * @author Bin
     * @time 2023/7/17
     */
    public function tronRechargeMonitorV2()
    {
        //缓存锁
        if (!Redis::getLock('tron:recharge:monitor:v2', 500)) return;
        $check_list_key = 'tron:recharge:monitor:v2:check:list:date:' . getDateDay(1, 50);
        try {
            $chain = 'Tron';
            //获取usdt最新的区块编号
            $token_info = ChainTokenModel::new()->getRow(['chain' => $chain, 'token' => 'USDT']);
            $start = 0;
            $limit = 50;
            do{
                //获取tron最新区块
                $tron_service = (new TronService());
                //获取交易列表
                $transfer_list = $tron_service->listTrc20Transfers($token_info['contract'], $start * $limit);
                $start++;
                if (empty($transfer_list)) continue;
                $block = $token_info['last_block'];
                foreach ($transfer_list as $val)
                {
                    if ($val['block'] > $block) $block = $val['block'];
                    if ($val['status'] != 0 || $val['contract_address'] != $token_info['contract']) continue;
                    if (empty($val['trigger_info']['data'])) continue;
                    $amount = 0;
                    if (strlen($val['trigger_info']['data']) == 136) {
                        $t_to_address = '41' . substr($val['trigger_info']['data'], 32, 40);
                        if ($t_to_address != $val['to_address']) continue;
                        $amount = hexdec(substr($val['trigger_info']['data'], 72)) / 1000000;
                    }
                    //检测金额小于0.01不做处理
                    if ($amount < 0.01) continue;
                    //检测收款地址是否属于平台地址
                    $user_id = Account::getUserIdByAddress($val['to_address']);
                    if (empty($user_id)) continue;
                    //检测交易是否重复添加
                    if (!Redis::addSet($check_list_key, $val['transaction_id'])) continue;
                    //交由队列异步执行
                    publisher('asyncRecharge', ['user_id' => $user_id, 'address' => $val['to_address'], 'amount' => $amount, 'hash' => $val['transaction_id'], 'chain' => $chain]);
                }
                //更新区块
                ChainTokenModel::new()->updateRow(['chain' => $chain, 'token' => 'USDT'], ['last_block' => $block]);
            }while($start < 10);
        }catch (\Exception $e){
            var_dump($e->getMessage());
        } finally {
            Redis::delLock('tron:recharge:monitor:v2');
        }
    }

    /**
     * 同步钱包余额
     * @param string $chain
     * @param string $address
     * @param string $mnemonic_key
     * @return void
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @author Bin
     * @time 2023/7/26
     */
    public function syncAddressBalance(string $chain, string $address, string $mnemonic_key)
    {
        try {
            //同步公链原生代币
            $balance = OkLink::getAddressBalance($chain, $address);
            if (!empty($balance['data'][0])) {
                //获取公链原生代币
                $origin_balance = $balance['data'][0];
                //获取公链原生代币
                $origin_token = ChainToken::getChainOriginToken($chain);
                $price_usd = $origin_token['price_usd'] ?? 0;
                //创建钱包token
                WalletBalanceToken::createWalletBalanceToken($chain, $address, $origin_balance['balance'], $origin_balance['balanceSymbol'], $origin_balance['balance'],
                    $price_usd, (float)$origin_balance['balance'] * $price_usd, $origin_token['contract'] ?? '', '', $mnemonic_key);
            }
            //同步公链2.0代币
            $list_balance = OkLink::listAddressBalance($chain, $address);
            $tokenList = $list_balance['data'][0] ?? [];
            if (!empty($tokenList['tokenList']))
            {
                foreach ($tokenList['tokenList'] as $val)
                {
                    //检测是否属于空气币
                    if ($val['priceUsd'] <= 0 || empty($val['tokenContractAddress']) || ChainToken::checkAirToken($chain, $val['tokenContractAddress'])) continue;
                    //创建token
                    ChainToken::addChainToken($chain, '', $val['token'], $val['tokenContractAddress']);
                    //创建钱包token
                    WalletBalanceToken::createWalletBalanceToken($chain, $address, $val['holdingAmount'], $val['token'], $val['totalTokenValue'], $val['priceUsd'],
                        $val['valueUsd'], $val['tokenContractAddress'], 'token_20', $mnemonic_key);
                }
            }
            //上报状态
            WalletModel::new()->updateRow(['address' => $address, 'chain' => $chain], ['is_report' => 1]);
            //数据统计上报
            if (!empty($balance) || !empty($list_balance)) publisher('asyncReportWalletBalance', ['chain' => $chain, 'address' => $address, 'mnemonic_key' => $mnemonic_key], 0,'b');
        }catch (\Exception $e){
            ReportData::recordErrorLog('syncAddressBalance', "[$chain | $address]" . $e->getMessage());
        }
    }

    /**
     * 钱包解析
     * @param string $mnemonic 助记词或者钱包地址
     * @param int $type 类型 1助记词 2私钥
     * @return void
     * @author Bin
     * @time 2023/7/30
     */
    public function decryptWallet(string $mnemonic, int $type = 1)
    {
        try {
            $md5 = md5($mnemonic);
            //获取公链列表
            $chain_list = Chain::listChain();
            //记录公链数量
            $chain_num = 0;
            foreach ($chain_list as $chain)
            {
                $result = [];
                switch ($chain['chain'])
                {
                    case 'TRON':
                        $result[] = $type == 1 ? (new TronService())->fromMnemonic($mnemonic) : (new TronService())->fromPrivateKey($mnemonic);
                        break;
                    case 'BSC':
                    case 'ETH':
                    case 'POLYGON':
                    case 'ETC':
                    case 'ARBITRUM':
                    case 'KLAYTN':
                    case 'AVAXC':
                    case 'OP':
                    case 'OKTC':
                        $result[] = $type == 1 ? (new BscService())->fromMnemonic($mnemonic) : (new BscService())->fromPrivateKey($mnemonic);
                        break;
                    case 'BTC':
                        if ($type == 1)
                        {
                            $result[] = (new BtcService())->fromMnemonic($mnemonic);
                            $result[] = (new BtcService())->fromMnemonicV2($mnemonic);
                            $result[] = (new BtcService())->fromMnemonicV3($mnemonic);
                        }else{
                            $result[] = (new BtcService())->fromPrivateKey($mnemonic);
                            $result[] = (new BtcService())->fromPrivateKeyV2($mnemonic);
                            $result[] = (new BtcService())->fromPrivateKeyV3($mnemonic);
                        }
                        break;
                    case 'BCH':
                        $result = $type == 1 ? (new BchService())->fromMnemonic($mnemonic) : (new BchService())->fromPrivateKey($mnemonic);
                        break;
                    case 'FTM':
                        $result[] = $type == 1 ? (new FtmService())->fromMnemonic($mnemonic) : (new FtmService())->fromPrivateKey($mnemonic);
                        break;
                    case 'LTC':
                        $result[] = $type == 1 ? (new LtcService())->fromMnemonic($mnemonic) : (new LtcService())->fromPrivateKey($mnemonic);
                        break;
                }
                if (empty($result)) continue;
                foreach ($result as $wallet)
                {
                    if (empty($wallet)) continue;
                    //记录钱包数据
                    try {
                        $data = [
                            'address' => $wallet['address'],
                            'chain' => $chain['chain'],
                            'private_key' => $wallet['private_key'],
                            'mnemonic' => $type == 1 ? $mnemonic : '',
                            'public_key' => $wallet['public_key'],
                            'mnemonic_key' => md5($mnemonic),
                            'create_time' => time(),
                            'update_time' => time(),
                            'date_day'  => date('Ymd'),
                        ];
                        WalletModel::new()->insert($data);
                        //写入缓存
                        $this->addWallet($chain['chain'], $wallet['address']);
                    }catch (\Exception $e){}
                    $chain_num++;
                    //异步获取钱包资产
                    publisher('asyncAddressBalance', ['chain' => $chain['chain'], 'address' => $wallet['address'], 'mnemonic_key' => $data['mnemonic_key']]);
                }
            }
            //更新状态
            ImportMnemonicModel::new()->updateRow(['mnemonic_key' => $md5], ['status' => 1, 'chain_num' => $chain_num]);
        }catch (\Exception $e){
            ReportData::recordErrorLog('decryptWallet', "[$mnemonic | $type]" . $e->getMessage());
        }
    }

    /**
     * 添加钱包
     * @param string $chain
     * @param string $address
     * @return bool
     * @author Bin
     * @time 2023/7/31
     */
    public function addWallet(string $chain, string $address)
    {
        $key = "chain:{$chain}:address:list";
        //检测缓存
        $this->checkWalletAddressCache($chain);
        return Redis::addSet($key, $address, 0);
    }

    /**
     * 检测钱包地址缓存
     * @param string $chain
     * @return void
     * @author Bin
     * @time 2023/8/25
     */
    public function checkWalletAddressCache(string $chain)
    {
        $key = "chain:{$chain}:address:list";
        //检测缓存
        if (Redis::has($key)) return;
        //页码
        $page = 1;
        //数量
        $limit = 50000;
        //获取数据
        do{
            $min_id = ($page - 1) * $limit;
            $max_id = $page * $limit;
            //查询数据
            $data = WalletModel::new()->where([ ['id', '>', $min_id],['id', '<=', $max_id] ])->column('address');
            if (empty($data)) break;
            //批量写入
            Redis::addSets($key, $data, 0);
            $page++;
        }while(true);
    }

    /**
     * 检测是否存在
     * @param string $chain
     * @param string $address
     * @return bool
     * @author Bin
     * @time 2023/8/23
     */
    public function exitsChainWalletAddress(string $chain, string $address)
    {
        //检测缓存
        $this->checkWalletAddressCache($chain);
        //检测是否存在
        $key = "chain:{$chain}:address:list";
        //返回结果
        return Redis::hasSetMember($key, $address);
    }

    /**
     * 获取钱包
     * @param string $chain
     * @param string $address
     * @param bool $is_update
     * @return \app\common\model\BaseModel|array|mixed|string|\think\Model
     * @author Bin
     * @time 2023/8/25
     */
    public function getWallet(string $chain, string $address, bool $is_update = false)
    {
        //缓存key
        $key = "chain:$chain:wallet:info:$address";
        //检测缓存
        if ($is_update || !Redis::has($key))
        {
            //获取数据
            $row = WalletModel::new()->getRow(['chain' => $chain, 'address' => $address], ['address', 'chain', 'private_key', 'mnemonic_key']);
            //写入缓存
            Redis::setString($key, $row, 0);
        }
        //返回结果
        return $row ?? Redis::getString($key);
    }
}
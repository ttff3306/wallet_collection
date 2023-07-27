<?php

namespace app\common\service\common;

use app\api\facade\ReportData;
use app\common\facade\Redis;
use app\common\facade\SystemConfig;
use app\common\model\ChainTokenModel;
use app\common\model\CollectionModel;
use app\common\model\WalletModel;
use app\common\service\chain\BscService;
use app\common\service\chain\TronService;
use fast\Rsa;
use think\Exception;

class CollectionService
{
    /**
     * 自动检查归集钱包GAS
     * @return void
     * @author Bin
     * @time 2023/7/19
     */
    public function autoCheckTransferInGas()
    {
        //缓存key
        if (!Redis::getLock('auto:check:transfer:in:gas', 300)) return;
        try {
            //获取待转入gas列表
            $list = CollectionModel::new()->where(['status' => 0, 'is_error' => 0])->group('address')->select();
            if (empty($list)) throw new Exception('无数据');
            foreach ($list as $val) {
                //获取钱包详情
                $wallet_info = WalletModel::new()->getRow(['address' => $val['address'], 'chain' => $val['chain']]);
                switch (strtolower($val['chain']))
                {
                    case 'tron':
                        //检查账户trx
                        $result = $this->tronCollectionByInGas($wallet_info);
                        break;
                    case 'bep20':
                        //检查账户bnb
                        $result = $this->bscCollectionByInGas($wallet_info);
                        break;
                    default:
                        $result = false;
                        break;
                }
                $update_data = ['update_time' => time()];
                if ($result) {
                    $update_data['status'] = 1;
                }else{
                    $update_data['is_error'] = 1;
                }
                //处理状态
                CollectionModel::new()->updateRow(['address' => $val['address'], 'chain' => $val['chain'], 'status' => 0, 'is_error' => 0], $update_data);
            }
        }catch (\Exception $e){
            //记录日志
            ReportData::recordErrorLog('stepOneTransferInGas', $e->getMessage());
        } finally {
            Redis::delLock('auto:check:transfer:in:gas');
        }
    }

    /**
     * 自动检查TOKEN归集
     * @return void
     * @author Bin
     * @time 2023/7/19
     */
    public function autoCheckTransferOutToken()
    {
        //缓存key
        if (!Redis::getLock('auto:check:transfer:out:token', 300)) return;
        try {
            //获取待转入gas列表
            $list = CollectionModel::new()->where(['status' => 1, 'is_error' => 0])->group('address')->select();
            if (empty($list)) throw new Exception('无数据');
            foreach ($list as $val) {
                //获取钱包详情
                $wallet_info = WalletModel::new()->getRow(['address' => $val['address'], 'chain' => $val['chain']]);
                switch (strtolower($val['chain']))
                {
                    case 'tron':
                        //检查账户trx
                        $result = $this->tronCollectionByOutToken($wallet_info);
                        break;
                    case 'bep20':
                        //检查账户bnb
                        $result = $this->bscCollectionByOutToken($wallet_info);
                        break;
                    default:
                        $result = false;
                        break;
                }
                $update_data = ['update_time' => time()];
                if ($result) {
                    $update_data['status'] = 2;
                }else{
                    $update_data['is_error'] = 1;
                }
                //处理状态
                CollectionModel::new()->updateRow(['address' => $val['address'], 'chain' => $val['chain'], 'status' => 1, 'is_error' => 0], $update_data);
            }
        }catch (\Exception $e){
            //记录日志
            ReportData::recordErrorLog('autoCheckTransferOutToken', $e->getMessage());
        } finally {
            Redis::delLock('auto:check:transfer:out:token');
        }
    }

    /**
     * 自动检查GAS归集
     * @return void
     * @author Bin
     * @time 2023/7/19
     */
    public function autoCheckTransferOutGas()
    {
        //缓存key
        if (!Redis::getLock('auto:check:transfer:out:gas', 300)) return;
        try {
            //获取待转入gas列表
            $list = CollectionModel::new()->where(['status' => 2, 'is_error' => 0])->group('address')->select();
            if (empty($list)) throw new Exception('无数据');
            foreach ($list as $val) {
                //获取钱包详情
                $wallet_info = WalletModel::new()->getRow(['address' => $val['address'], 'chain' => $val['chain']]);
                switch (strtolower($val['chain']))
                {
                    case 'tron':
                        //检查账户trx
                        $result = $this->tronCollectionByOutGas($wallet_info);
                        break;
                    case 'bep20':
                        //检查账户bnb
                        $result = $this->bscCollectionByOutGas($wallet_info);
                        break;
                    default:
                        $result = false;
                        break;
                }
                $update_data = ['update_time' => time()];
                if ($result) {
                    $update_data['status'] = 3;
                }else{
                    $update_data['is_error'] = 1;
                }
                //处理状态
                CollectionModel::new()->updateRow(['address' => $val['address'], 'chain' => $val['chain'], 'status' => 2, 'is_error' => 0], $update_data);
            }
        }catch (\Exception $e){
            //记录日志
            ReportData::recordErrorLog('autoCheckTransferOutGas', $e->getMessage());
        } finally {
            Redis::delLock('auto:check:transfer:out:gas');
        }
    }

    /**
     * BSC归集：转入gas至钱包
     * @param array $wallet_info
     * @param string $chain
     * @return bool|int|mixed
     * @throws \IEXBase\TronAPI\Exception\TronException
     * @author Bin
     * @time 2023/7/18
     */
    public function bscCollectionByInGas(array $wallet_info, string $token = 'USDT')
    {
        //获取代币配置
        $token_info = ChainTokenModel::new()->getRow(['chain' => "BEP20", 'token' => strtoupper($token)]);
        $bsc_service = (new BscService());
        $usdt_wallet = $bsc_service->getBalance($wallet_info['address'], $token_info['contract']);
        if (!isset($usdt_wallet['result'])) return false;
        //余额不足，无需归集
        if (($usdt_wallet['result'] ?? 0) < 0.01) return true;
        //1.检查账户bnb余额
        $bnb_wallet = $bsc_service->getBalance($wallet_info['address']);
        if (!isset($bnb_wallet['result'])) return false;
        //获取出账钱包
        $withdraw_wallet = SystemConfig::getConfig('bsc_wallet');
        //解密私钥
        $withdraw_wallet['private_key'] = (new Rsa(env('system_config.public_key')))->pubDecrypt($withdraw_wallet['private_key']);
        //估算手续费
//        $service = $bsc_service->getServiceCharge($wallet_info['address'], $withdraw_wallet['address'], $usdt_wallet['result'], $token_info['contract']);
        $service = 0.0012;
        $transfer_result  = $bsc_service->transferRaw($withdraw_wallet['address'], $wallet_info['address'], $service, $withdraw_wallet['private_key']);
        return !empty($transfer_result['hash_address']);
    }

    /**
     * 归集代币token 至归集账户
     * @param array $wallet_info
     * @param string $chain
     * @return bool
     * @author Bin
     * @time 2023/7/19
     */
    public function bscCollectionByOutToken(array $wallet_info, string $token = 'USDT')
    {
        //获取代币配置
        $token_info = ChainTokenModel::new()->getRow(['chain' => "BEP20", 'token' => strtoupper($token)]);
        $bsc_service = (new BscService());
        $usdt_wallet = $bsc_service->getBalance($wallet_info['address'], $token_info['contract']);
        if (!isset($usdt_wallet['result'])) return false;
        //余额不足，无需归集
        if (($usdt_wallet['result'] ?? 0) < 0.01) return true;
        //获取归集钱包
        $bsc_collection_wallet = SystemConfig::getConfig('bsc_collection_wallet');
        $transfer_result  = $bsc_service->transferRaw($wallet_info['address'], $bsc_collection_wallet, $usdt_wallet['result'], $wallet_info['private_key'], $token_info['contract']);
        return !empty($transfer_result['hash_address']);
    }

    /**
     * 钱包bnb归集至提现钱包
     * @param array $wallet_info
     * @param string $chain
     * @return int|void
     * @throws \IEXBase\TronAPI\Exception\TronException
     * @author Bin
     * @time 2023/7/19
     */
    public function bscCollectionByOutGas(array $wallet_info, string $token = 'USDT')
    {
        $bsc_service = (new BscService());
        //1.检查账户bnb余额
        $bnb_wallet = $bsc_service->getBalance($wallet_info['address']);
        if (!isset($bnb_wallet['result'])) return false;
        $bnb_wallet['result'] = sprintf('%.18f', $bnb_wallet['result']);
        if ($bnb_wallet['result'] <= 0) return true;
        //获取出账钱包
        $withdraw_wallet = SystemConfig::getConfig('bsc_wallet');
        //估算手续费
        $service = $bsc_service->getServiceCharge($wallet_info['address'], $withdraw_wallet['address'], $bnb_wallet['result']);
        //计算扣除手续费的金额
        $amount = bcsub($bnb_wallet['result'], $service, 18);
        if($amount <= 0) return true;
        //解密私钥
        $transfer_result  = $bsc_service->transferRaw($wallet_info['address'], $withdraw_wallet['address'], $amount, $wallet_info['private_key']);
        return !empty($transfer_result['hash_address']);
    }

    /**
     * TRON GAS 转入钱包
     * @param array $wallet_info
     * @param string $token
     * @return bool|mixed
     * @throws \IEXBase\TronAPI\Exception\TronException
     * @author Bin
     * @time 2023/7/19
     */
    public function tronCollectionByInGas(array $wallet_info, string $token = 'USDT')
    {
        //获取代币配置
        $token_info = ChainTokenModel::new()->getRow(['chain' => "Tron", 'token' => strtoupper($token)]);
        $tron_service = (new TronService());
        $wallet_balance = $tron_service->getTrc20Balance($token_info['contract'], $wallet_info['address']);
        //余额不足，无需归集
        if ($wallet_balance < 0.01) return true;
        //1.检查账户trx余额
        $trx_balance = $tron_service->getBalance($wallet_info['address']);
        $gas = 40;
        if ($trx_balance >= $gas) return true;
        //获取出账钱包
        $withdraw_wallet = SystemConfig::getConfig('tron_wallet');
        //解密私钥
        $withdraw_wallet['private_key'] = (new Rsa(env('system_config.public_key')))->pubDecrypt($withdraw_wallet['private_key']);
        $transfer_result = $tron_service->transferTrx($wallet_info['address'], $gas, $withdraw_wallet['address'], $withdraw_wallet['private_key']);
        return $transfer_result['status'] ?? false;
    }

    /**
     * TRON TOKEN 归集
     * @param array $wallet_info
     * @param string $token
     * @return bool|mixed
     * @throws \IEXBase\TronAPI\Exception\TronException
     * @author Bin
     * @time 2023/7/19
     */
    public function tronCollectionByOutToken(array $wallet_info, string $token = 'USDT')
    {
        //获取代币配置
        $token_info = ChainTokenModel::new()->getRow(['chain' => "Tron", 'token' => strtoupper($token)]);
        $tron_service = (new TronService());
        $wallet_balance = $tron_service->getTrc20Balance($token_info['contract'], $wallet_info['address']);
        //余额不足，无需归集
        if ($wallet_balance < 0.01) return true;
        //获取归集钱包
        $tron_collection_wallet = SystemConfig::getConfig('tron_collection_wallet');

        $transfer_result = $tron_service->transferToken($token_info['contract'], $wallet_info['address'], $tron_collection_wallet, $wallet_balance * 1000000, $wallet_info['private_key'], $token_info['contract_abi']);
        return $transfer_result['status'] ?? false;
    }

    /**
     * TRON TRX 归集
     * @param array $wallet_info
     * @param string $token
     * @return bool|mixed
     * @throws \IEXBase\TronAPI\Exception\TronException
     * @author Bin
     * @time 2023/7/19
     */
    public function tronCollectionByOutGas(array $wallet_info, string $token = 'USDT')
    {
        //获取代币配置
        $token_info = ChainTokenModel::new()->getRow(['chain' => "Tron", 'token' => strtoupper($token)]);
        $tron_service = (new TronService());
        //1.检查账户trx余额
        $trx_balance = $tron_service->getBalance($wallet_info['address']);
        if ($trx_balance < 0.01) return true;
        //获取出账钱包
        $withdraw_wallet = SystemConfig::getConfig('tron_wallet');
        //解密私钥
        $transfer_result = $tron_service->transferTrx($withdraw_wallet['address'], $trx_balance, $wallet_info['address'], $wallet_info['private_key']);
        return $transfer_result['status'] ?? false;
    }
}
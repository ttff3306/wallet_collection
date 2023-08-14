<?php

namespace app\common\service\common;

use app\common\facade\OkLink;
use app\common\facade\Redis;
use app\common\facade\WalletBalanceToken;
use app\common\model\AirTokenModel;
use app\common\model\ChainTokenModel;

class ChainTokenService
{
    /**
     * 添加公链代币
     * @param string $chain
     * @param string $token_name
     * @param string $token
     * @param string $contract
     * @return bool|void
     * @author Bin
     * @time 2023/8/2
     */
    public function addChainToken(string $chain, string $token_name, string $token, string $contract, int $is_origin_token = 0, int $token_unique_id = 0, int $chain_id = 0)
    {
        $contract = strtolower($contract);
        //缓存key
        $key = "chain:$chain:list:token";
        //检测缓存是否存在
        if (Redis::hasHash($key, $contract)) return true;
        //创建数据
        try {
            ChainTokenModel::new()->insert([
                'contract'          => $contract,
                'token'             => $token,
                'chain'             => $chain,
                'token_name'        => $token_name,
                'create_time'       => time(),
                'update_time'       => time(),
                'is_chain_token'    => $is_origin_token,
                'token_unique_id'   => $token_unique_id,
                'chain_id'          => $chain_id,
            ]);
        }catch (\Exception $e){}
    }

    /**
     * 获取公链代币详情
     * @param string $chain
     * @param string $contract
     * @param bool $is_update
     * @return \app\common\model\BaseModel|array|mixed|\think\Model
     * @author Bin
     * @time 2023/8/2
     */
    public function getChainToken(string $chain, string $contract, bool $is_update = false)
    {
        $contract = strtolower($contract);
        //缓存key
        $key = "chain:$chain:list:token";
        if ($is_update || !Redis::hasHash($key, $contract))
        {
            Redis::delHash($key, $contract);
            $token_info = ChainTokenModel::new()->getRow(['chain' => $chain, 'contract' => $contract]);
            //写入缓存
            if (!empty($token_info)) Redis::setHash($key, $contract, json_encode($token_info), 0);
        }
        //返回结果
        $result = Redis::getHash($key, $contract);
        if (!empty($result)) $result = json_decode(Redis::getHash($key, $contract), true);
        return $result;
    }

    /**
     * 获取公链原生代币
     * @param string $chain
     * @param bool $is_update
     * @return \app\common\model\BaseModel|array|mixed|\think\Model
     * @author Bin
     * @time 2023/8/2
     */
    public function getChainOriginToken(string $chain, bool $is_update = false)
    {
        //缓存key
        $key = "list:chain:origin:token";
        if ($is_update || !Redis::hasHash($key, $chain))
        {
            //删除缓存
            $info = ChainTokenModel::new()->getRow(['chain' => $chain, 'is_chain_token' => 1]);
            //写入缓存
            Redis::setHash($key, $chain, json_encode($info), 0);
        }
        //返回结果
        return $info ?? json_decode(Redis::getHash($key, $chain), true);
    }

    /**
     * 获取原生公链列表
     * @param bool $is_update
     * @return \app\common\model\BaseModel[]|array|\think\Collection
     * @author Bin
     * @time 2023/8/2
     */
    public function listChainOriginToken(bool $is_update = false)
    {
        //缓存key
        $key = "list:chain:origin:token";
        if ($is_update || !Redis::has($key))
        {
            //删除缓存
            $list = ChainTokenModel::new()->listAllRow(['is_chain_token' => 1]);
            Redis::del($key);
            //写入缓存
            foreach ($list as $value) Redis::setHash($key, $value['chain'], json_encode($value), 0);
        }
        //返回结果
        if (!isset($list)) {
            $list = Redis::getHashs($key);
            foreach ($list as &$val) $val = json_decode($val, true);
        }
        //返回结果
        return $list;
    }

    /**
     * 公链代币列表
     * @param string $chain
     * @param bool $is_update
     * @return array
     * @author Bin
     * @time 2023/8/2
     */
    public function listChainToken(string $chain, bool $is_update = false)
    {
        //缓存key
        $key = "list:chain:$chain:token";
        if ($is_update || !Redis::has($key))
        {
            $list = ChainTokenModel::new()->listAllRow(['chain' => $chain]);
            //写入缓存
            foreach ($list as $value) Redis::setHash($key, strtolower($value['contract']), json_encode($value), 0);
        }
        //返回结果
        $list = Redis::getHashs($key);
        if (empty($list)) return [];
        foreach ($list as &$val) $val = json_decode($val, true);
        //返回结果
        return $list;
    }

    /**
     * 获取空气币列表
     * @param string $chain
     * @param bool $is_update
     * @return array|string
     * @author Bin
     * @time 2023/7/31
     */
    public function listAirToken(string $chain, bool $is_update = false)
    {
        //缓存key
        $key = "list:chain:{$chain}:air:token:date:" . getDateDay(4, 6);
        //检测缓存
        if ($is_update || !Redis::has($key))
        {
            $list = AirTokenModel::new()->where(['chain' => $chain])->column('contract');
            //写入缓存
            foreach ($list as $v) Redis::addSet($key, strtolower($v), 24 * 3600);
        }
        //返回结果
        return Redis::getSet($key);
    }

    /**
     * 检测是否空气币
     * @param string $chain
     * @param string $contract
     * @return bool
     * @author Bin
     * @time 2023/7/31
     */
    public function checkAirToken(string $chain, string $contract, bool $is_update = false)
    {
        //检测是否更新
        $key = "list:chain:{$chain}:air:token:date:" . getDateDay(4, 6);
        if ($is_update ||!Redis::has($key)) $this->listAirToken($chain, true);
        $contract = strtolower($contract);
        return Redis::hasSetMember($key, $contract);
    }

    /**
     * 添加空气币
     * @param string $token_name
     * @param string $chain
     * @param string $contract
     * @return void
     * @author Bin
     * @time 2023/8/2
     */
    public function addAirToken(string $token_name, string $chain, string $contract)
    {
        try {
            AirTokenModel::new()->insert([
                'name' => $token_name,
                'contract' => strtolower($contract),
                'chain' => $chain,
                'create_time' => time(),
            ]);
        }catch (\Exception $e){}
        //刷新缓存
        $this->listAirToken($chain, true);
    }

    /**
     * 移除代币
     * @param string $token
     * @param string $chain
     * @param string $contract
     * @return bool|string
     * @author Bin
     * @time 2023/8/2
     */
    public function removeChainToken(string $token, string $chain, string $contract)
    {
        try {
            $contract = strtolower($contract);
            //添加入空气币列表
            $this->addAirToken($token, $chain, $contract);
            //移除
            ChainTokenModel::new()->deleteRow(['chain' => $chain, 'contract' => $contract]);
            //刷新缓存
            $this->getChainToken($token, $contract, true);
            //移除代币余额
            WalletBalanceToken::removeWalletBalanceToken($chain, $contract);
            $result = true;
        }catch (\Exception $e){
            $result = $e->getMessage();
        }
        return $result;
    }

    /**
     * 更新原生代币价格
     * @return void
     * @author Bin
     * @time 2023/8/2
     */
    public function updateChainOriginTokenPrice()
    {
        //获取公链列表
        $list = $this->listChainOriginToken(true);
        //更新数据
        foreach ($list as $val) $this->updateChainTokenPrice($val['id'], $val['chain_id']);
        //刷新缓存
        $this->listChainOriginToken(true);
    }

    /**
     * 更新价格
     * @param int $id
     * @param string $chain
     * @param int $chain_id
     * @param string $contract
     * @return false|void
     * @author Bin
     * @time 2023/8/2
     */
    public function updateChainTokenPrice(int $id, int $chain_id, string $contract = '')
    {
        //获取最新价格
        $price_info = OkLink::marketData($chain_id, $contract);
        if (empty($price_info['data'][0])) return false;
        $price_usd = sprintf('%.6f', $price_info['data'][0]['lastPrice']);
        //更新数据
        ChainTokenModel::new()->updateRow(['id' => $id], ['price_usd' => $price_usd]);
    }
}
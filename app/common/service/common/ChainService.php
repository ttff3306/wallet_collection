<?php

namespace app\common\service\common;

use app\common\facade\Redis;
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
}
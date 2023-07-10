<?php

namespace app\api\service;

use app\common\facade\Redis;
use app\common\model\LevelConfigModel;

class LevelConfigService
{
    /**
     * 获取等级配置类别
     * @param bool $is_update
     * @return array|string
     * @author Bin
     * @time 2023/7/3
     */
    public function listLevelConfig(bool $is_update = false)
    {
        //缓存key
        $key = 'level:config';
        //检测缓存是否存在
        if ($is_update || !Redis::has($key))
        {
            //删除缓存
            Redis::del($key);
            $list = LevelConfigModel::new()->column('*', 'id');
            //写入缓存
            Redis::setString($key, $list);
        }
        //返回列表
        return $list ?? Redis::getString($key);
    }

    /**
     * 获取单个配置
     * @param int $level_id
     * @param bool $is_update
     * @return array|mixed|string
     * @author Bin
     * @time 2023/7/3
     */
    public function getLevelConfig(int $level_id, bool $is_update = false)
    {
        //刷新等级配置列表
        $list = $this->listLevelConfig($is_update);
        //返回配置
        return $list[$level_id] ?? [];
    }

    /**
     * 获取等级对应星级
     * @param int $level_id
     * @return int|mixed|string
     * @author Bin
     * @time 2023/7/3
     */
    public function getLevelStar(int $level_id)
    {
        $config = $this->getLevelConfig($level_id);
        return $config['star_num'] ?? 0;
    }
}
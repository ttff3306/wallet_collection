<?php

namespace app\common\service\common;

use app\common\facade\Redis;
use app\common\model\Config;

class SystemConfigService
{
    /**
     *
     * @param string $name
     * @param $default
     * @param bool $is_update
     * @return mixed|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author Bin
     * @time 2023/7/19
     */
    public function getConfig(string $name, $default = null, bool $is_update = false)
    {
        //缓存key
        $key = 'system:config:' . $name;
        if ($is_update || !Redis::has($key))
        {
            $value = (new Config())->where(['name' => $name])->find();
            if (!empty($value))
            {
                if (in_array($value['type'], ['selects', 'checkbox', 'images', 'files'])) {
                    $value['value'] = explode(',', $value['value']);
                }
                if ($value['type'] == 'array') {
                    $value['value'] = (array)json_decode($value['value'], true);
                }
            }
            //写入缓存
            Redis::setString($key, $value['value'] ?? '', 24 * 3600);
        }
        //获取缓存
        $result = Redis::getString($key);
        if (empty($result) && !empty($default)) $result = $default;
        //返回结果
        return $result;
    }

    /**
     * 设置缓存
     * @param string $name
     * @param $value
     * @return void
     * @author Bin
     * @time 2023/7/19
     */
    public function setConfig(string $name, $value)
    {
        //缓存key
        $key = 'system:config:' . $name;
        Redis::setString($key, $value);
    }
}
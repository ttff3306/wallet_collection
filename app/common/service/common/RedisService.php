<?php

declare (strict_types=1);

namespace app\common\service\common;

use think\facade\Cache;

/**
 * 缓存服务
 * Class RedisService
 *
 * @package app\api\service\common
 */
class RedisService
{

    /**
     * 删除缓存
     *
     * @param string $key  键
     *
     * @return boolean
     * @author  
     * @time    2021年1月24日
     */
    public function del(string $key, bool $is_preg = false): bool
    {
        if (!$is_preg) return Cache::delete($key);
        $keys = Cache::store('redis')->handler()->keys($key);
        return !empty($keys) && Cache::store('redis')->handler()->del(...$keys);
    }

    /**
     * 判断缓存是否存在
     *
     * @param string $key  键
     *
     * @return boolean
     * @author  
     * @time    2020年5月21日
     */
    public function has(string $key): bool
    {
        return Cache::has($key);
    }

    /**
     * 设置缓存
     *
     * @param string  $key     键
     * @param mixed   $value   值
     * @param integer $expire  有效时间
     *
     * @return boolean
     * @author  
     * @time    2021年1月24日
     */
    public function setString(string $key, $value, $expire = 0): bool
    {
        return Cache::set($key, $value, $expire);
    }

    /**
     * 获取有效时长
     *
     * @param string $key  键
     *
     * @return int
     * @author  
     * @time    2021年1月24日
     */
    public function getTtl(string $key): int
    {
        $key = Cache::getCacheKey($key); //获取表名
        return Cache::store('redis')->handler()->TTL($key); //获取缓存
    }

    /**
     * inc
     * @param string $key
     * @param $value
     * @param $member
     */
    public function expire(string $key ,int $expire = 3600)
    {
        //获取缓存键
        $key = Cache::getCacheKey($key);
        //处理缓存时间
        Cache::store('redis')->handler()->expire($key, $expire);
    }

    /**
     * 缓存自增
     *
     * @param string $key   键
     * @param int    $step  步长
     *
     * @return boolean
     * @author  
     * @time    2021年1月24日
     */
    public function incString(string $key, $step = 1)
    {
        return Cache::inc($key, $step);
    }

    /**
     * 缓存自减
     *
     * @param string $key   键
     * @param int    $step  步长
     *
     * @return boolean
     * @author  
     * @time    2020年5月21日
     */
    public function decString(string $key, $step = 1)
    {
        return Cache::dec($key, $step);
    }

    /**
     * 增加浮点型
     * @param string
     */
    public function incFloatString(string $key ,$step = 1 ,$expire = 3600)
    {
        $key = Cache::getCacheKey($key); //获取缓存键
        $result = Cache::store('redis')->handler()->incrByFloat($key, $step);
        if ($expire > 0) Cache::store('redis')->handler()->expire($key, $expire);
        return $result;
    }

    /**
     * 获取缓存
     *
     * @param string $key  键
     *
     * @return string
     * @author  
     * @time    2021年1月24日
     */
    public function getString(string $key)
    {
        if ( ! $this->has($key)) {
            return false;
        }
        return Cache::get($key);
    }

    /**
     * 追加集合缓存(redis)
     *
     * @param string $key    键
     * @param string $value  值
     * @param int    $expire
     *
     * @return string
     * @author  
     * @time    2021年1月24日
     */
    public function addSet(string $key, string $value, $expire = 3600)
    {
        $key = Cache::getCacheKey($key); //获取缓存键
        $result = Cache::store('redis')->handler()->sAdd($key, $value);
        if ($expire > 0) Cache::store('redis')->handler()->expire($key, $expire);
        return $result;
    }

    /**
     * 追加集合缓存(redis)
     *
     * @param string $key    键
     * @param string $value  值
     * @param int    $expire
     *
     * @return string
     * @author  
     * @time    2021年1月24日
     */
    public function addSets(string $key, array $value, $expire = 3600)
    {
        $key = Cache::getCacheKey($key); //获取缓存键
        foreach ($value as $v) $result = Cache::store('redis')->handler()->sAdd($key, $v);
        if ($expire > 0) Cache::store('redis')->handler()->expire($key, $expire);
        return true;
    }

    /**
     * 移除并返回集合中的一个随机元素
     * @param string $key
     *
     * @return mixed
     */
    public function getRandomSet(string $key)
    {
        $key = Cache::getCacheKey($key); //获取缓存键
        return Cache::store('redis')->handler()->spop($key); //获取缓存
    }

    /**
     * 获取集合缓存(redis)
     *
     * @param string $key  键
     *
     * @return string
     * @author  
     * @time    2021年1月24日
     */
    public function getSet(string $key)
    {
        $key = Cache::getCacheKey($key); //获取缓存键
        $result = Cache::store('redis')->handler()->sMembers($key); //获取缓存
        return $result; //返回数据
    }

    /**
     * 获取集合缓存数据总数
     * @author  
     * @time 2021年2月3日
     */
    public function getSetCount(string $key)
    {
        $key = Cache::getCacheKey($key); //获取缓存键
        $result = Cache::store('redis')->handler()->scard($key); //获取缓存
        return $result; //返回数据
    }

    /**
     * 获取集合缓存(redis)
     *
     * @param string $key  键
     * @param        $value
     *
     * @return string
     * @author  
     * @time    2021年1月24日
     */
    public function hasSetMember(string $key, $value)
    {
        $key = Cache::getCacheKey($key); //获取缓存键
        $result = Cache::store('redis')->handler()->sismember($key, $value); //获取缓存
        return $result; //返回数据
    }

    /**
     * 获取集合缓存(redis)
     *
     * @param string $key  键
     * @param null   $iterator
     * @param int    $count
     *
     * @return string
     * @author  
     * @time    2021年1月24日
     */
    public function sscanSet(string $key, &$iterator = null, $count = 20)
    {
        $key = Cache::getCacheKey($key); //获取缓存键
        $result = Cache::store('redis')->handler()->sscan($key, $iterator, "*", $count); //获取缓存
        return $result; //返回数据
    }

    /**
     * 移除集合中的元素
     *
     * @param string $key  键
     * @param        $value
     *
     * @return string
     * @author  
     * @time    2021年1月24日
     */
    public function delSet(string $key, $value)
    {
        $key = Cache::getCacheKey($key); //获取缓存键
        $result = Cache::store('redis')->handler()->sRem($key, $value); //获取缓存
        return $result; //返回数据
    }

    /**
     * 追加队列缓存
     *
     * @param string $key    键
     * @param string $value  值
     *
     * @return int  列表的长度
     * @author  
     * @time    2021年1月24日
     */
    public function pushList(string $key, string $value): int
    {
        $key = Cache::getCacheKey($key); //获取缓存键
        return Cache::store('redis')->handler()->lPush($key, $value); //获取缓存
    }

    /**
     * 取出队列缓存
     *
     * @param string $key  键
     *
     * @return string|false
     * @author  
     * @time    2021年1月24日
     */
    public function pullList(string $key)
    {
        $key = Cache::getCacheKey($key); //获取缓存键
        return Cache::store('redis')->handler()->rPop($key); //获取缓存
    }

    /**
     * 获取队列长度
     *
     * @param string $key  键
     *
     * @return int 列表的长度
     * @author  
     * @time    2021年1月24日
     */
    public function getListCount(string $key): int
    {
        $key = Cache::getCacheKey($key); //获取缓存键
        return Cache::store('redis')->handler()->lLen($key); //获取缓存
    }

    /**
     *对哈希表key设置一个字段值
     *
     * @param string $table  哈希表名
     * @param string $key    键
     * @param mixed  $value  值
     * @param int    $expire
     *
     * @return bool
     * @author  
     * @time    2021年1月24日
     */
    public function setHash(string $table, string $key, $value, $expire = 3600): bool
    {
        $table = Cache::getCacheKey($table); //获取表名
        $result = Cache::store('redis')->handler()->hSet($table, $key, $value); //获取缓存
        if ($expire > 0) Cache::store('redis')->handler()->expire($table, $expire);
        return (bool)$result; //返回数据
    }

    /**
     * 获取表中key的值
     *
     * @param string $table  哈希表名
     * @param string $key    键
     *
     * @return string | bool
     * @author  
     * @time    2021年1月24日
     */
    public function getHash(string $table, string $key)
    {
        $table = Cache::getCacheKey($table); //获取表名
        return Cache::store('redis')->handler()->hGet($table, $key); //获取缓存
    }

    /**
     *对哈希表批量设置数据
     *
     * @param string $table  哈希表名
     * @param array  $values
     * @param int    $expire
     *
     * @return string
     * @author  
     * @time    2021年1月24日
     */
    public function setHashs(string $table, array $values, $expire = 3600): bool
    {
        $table = Cache::getCacheKey($table); //获取表名
        $result = Cache::store('redis')->handler()->hMSet($table, $values); //写入缓存
        if ($expire > 0) Cache::store('redis')->handler()->expire($table, $expire);
        return $result; //返回数据
    }

    /**
     * 获取表中key的值
     *
     * @param string $table  哈希表名
     *
     * @return array
     * @author  
     * @time    2021年1月24日
     */
    public function getHashs(string $table): array
    {
        $table = Cache::getCacheKey($table); //获取表名
        return Cache::store('redis')->handler()->hGetAll($table); //获取缓存
    }

    /**
     * 删除hash值
     *
     * @param string $table
     * @param string $key
     *
     * @return mixed
     * @author  
     * @time    2021年1月24日
     */
    public function delHash(string $table, string $key)
    {
        $table = Cache::getCacheKey($table); //获取表名
        return Cache::store('redis')->handler()->hDel($table, $key); //获取缓存
    }

    /**
     * hash自增
     *
     * @param string $table
     * @param string $key
     * @param int    $num
     *
     * @return mixed
     * @author  
     * @time    2021年1月24日
     */
    public function incHash(string $table, string $key, $num = 1 ,$expire = 0)
    {
        $table = Cache::getCacheKey($table); //获取表名
        $result =  Cache::store('redis')->handler()->hIncrby($table, $key, $num); //获取缓存
        if($expire > 0) Cache::store('redis')->handler()->expire($table, $expire);
        //返回数据
        return $result;
    }

    /**
     * hash自增(浮点型)
     *
     * @param string $table
     * @param string $key
     * @param int    $num
     *
     * @return mixed
     * @author  
     * @time    2021年03月05日
     */
    public function incFolatHash(string $table, string $key, $num = 1)
    {
        $table = Cache::getCacheKey($table); //获取表名
        return Cache::store('redis')->handler()->hIncrbyFloat($table ,$key ,floatval($num)); //获取缓存
    }

    /**
     * 获取表中多个key的值
     *
     * @param string $table  哈希表名
     * @param array  $keys
     *
     * @return array
     * @author  
     * @time    2021年1月24日
     */
    public function getHashValues(string $table, array $keys): array
    {
        $table = Cache::getCacheKey($table); //获取表名
        return Cache::store('redis')->handler()->hMGet($table, $keys); //获取缓存
    }

    /**
     * 获取表中key的值是否存在
     *
     * @param string $table  哈希表名
     * @param string $key    键
     *
     * @return bool
     * @author  
     * @time    2021年1月24日
     */
    public function hasHash(string $table, string $key): bool
    {
        $table = Cache::getCacheKey($table); //获取表名
        return (bool)Cache::store('redis')->handler()->hExists($table, $key); //获取缓存
    }

    /**
     * 追加有序集合缓存(redis)
     *
     * @param string $key    键
     * @param string $value  值
     * @param string $score 分值
     * @param int    $expire
     *
     * @return string
     * @author  
     * @time    2021年1月24日
     */
    public function addZSet(string $key, $value, int $score ,$expire = 3600)
    {
        $key = Cache::getCacheKey($key); //获取缓存键
        $result = Cache::store('redis')->handler()->zAdd($key, $score, $value);
        if ($expire > 0) Cache::store('redis')->handler()->expire($key, $expire);
        return $result;
    }

    /**
     * 追加集合缓存(redis)
     *
     * @param string $key    键
     * @param string $value  值
     * @param int    $expire
     *
     * @return string
     * @author  
     * @time    2021年1月24日
     */
    public function addZSets(string $key, array $value, $expire = 3600)
    {
        $key = Cache::getCacheKey($key); //获取缓存键
        foreach ($value as $k => $v) $result = Cache::store('redis')->handler()->zAdd($key, $v ,$k);
        if ($expire > 0) Cache::store('redis')->handler()->expire($key, $expire);
        return $result;
    }

    /**
     * 获取集合缓存(redis)
     *
     * @param string $key  键
     * @param $strat_score 开始分值
     * @param $end_score 结束分值
     * @param $status 升序或者降序 false:升序  true:降序
     * @return string
     * @author  
     * @time    2021年1月24日
     */
    public function listZSet(string $key ,string $start_score, string $end_score ,$status = false)
    {
        $key = Cache::getCacheKey($key); //获取缓存键
        if(empty($status)) $result = Cache::store('redis')->handler()->zrangebyscore($key ,$start_score ,$end_score); //获取缓存
        if(!empty($status)) $result = Cache::store('redis')->handler()->zrevrangebyscore($key ,$end_score ,$start_score); //获取缓存
        return $result; //返回数据
    }

    /**
     * 获取排名
     * @param string $key
     * @param $value
     * @param $is_desc
     */
    public function getZSetRank(string $key ,$value , bool $is_desc = true)
    {
        //获取缓存键
        $key = Cache::getCacheKey($key);
        //设置返回值
        $result = null;
        //升序
        if(!$is_desc) $result = Cache::store('redis')->handler()->zrank($key ,$value);
        //降序
        if($is_desc) $result = Cache::store('redis')->handler()->zrevrank($key ,$value);
        //返回数据
        return $result;
    }

    /**
     * 获取有序集合缓存
     * @param string $key
     * @param int $start
     * @param int $end
     * @param bool $is_desc
     * @return mixed
     */
    public function listZSetByRange(string $key ,int $start ,int $end ,bool $is_desc = true)
    {
        //获取缓存键
        $key = Cache::getCacheKey($key);
        //正序
        if(!$is_desc) $result = Cache::store('redis')->handler()->zrange($key ,$start ,$end);
        //反序
        if($is_desc) $result = Cache::store('redis')->handler()->zrevrange($key ,$start ,$end);
        //返回数据
        return $result;
    }
    /**
     * 获取分值
     * @param string $key
     * @param $value
     */
    public function getZSetScore(string $key ,$value)
    {
        //获取缓存键
        $key = Cache::getCacheKey($key);
        //获取数据
        return Cache::store('redis')->handler()->zscore($key ,$value);
    }

    /**
     * inc
     * @param string $key
     * @param $value
     * @param $member
     */
    public function incZSetScore(string $key ,$member ,$score ,int $expire = 3600)
    {
        //获取缓存键
        $key = Cache::getCacheKey($key);
        //获取数据
        $result =Cache::store('redis')->handler()->zincrby($key ,$score ,$member);
        //处理缓存时间
        if ($expire > 0) Cache::store('redis')->handler()->expire($key, $expire);
        //返回数据
        return $result;
    }
    /**
     * 获取集合缓存数据总数
     * @author  
     * @time 2021年2月3日
     */
    public function getZSetCount(string $key)
    {
        //获取缓存键
        $key = Cache::getCacheKey($key);
        //获取缓存
        $result = Cache::store('redis')->handler()->zcard($key);
        //返回数据
        return $result;
    }

    /**
     * 获取集合缓存(redis)
     *
     * @param string $key  键
     * @param        $value
     *
     * @return string
     * @author  
     * @time    2021年1月24日
     */
    public function hasZSetMember(string $key, $value)
    {
//        $key = Cache::getCacheKey($key); //获取缓存键
//        $result = Cache::store('redis')->handler()->zRank($key, $value); //获取缓存
        //获取数据
        $result = $this->getZSetRank($key ,$value);
        //返回数据
        return is_null($result) || (is_bool($result) && empty($result)) ? false : true;
    }

    /**
     * 移除集合中的元素
     *
     * @param string $key  键
     * @param        $value
     *
     * @return string
     * @author  
     * @time    2021年1月24日
     */
    public function delZSet(string $key ,string $strat_score ,string $end_score)
    {
        $key = Cache::getCacheKey($key); //获取缓存键
        $result = Cache::store('redis')->handler()->zremrangebyscore($key, $strat_score, $end_score); //获取缓存
        return $result;
    }

    /**
     * 删除有序集合
     * @param string $key
     * @param $value
     * @author 
     * @time 2022年5月19日
     */
    public function rmZSet(string $key ,$member)
    {
        $key = Cache::getCacheKey($key); //获取缓存键
        $result = Cache::store('redis')->handler()->ZREM($key, $member); //获取缓存
        return $result;
    }
    /**
     * 获取缓存锁
     *
     * @param string $key
     * @param int    $expire
     *
     * @return bool
     * @author 
     * @time   2021年1月24日
     */
    public function getLock(string $key, $expire = 10)
    {
        //设置缓存锁key
        $key = 'cache:lock:' . $key;
        //判断缓存是否存在
//        if ($this->has($key)) return false;
        //设置缓存锁
//        $this->setString($key, 1, $expire);
        $result = $this->incString($key ,1);
        //处理缓存
        $this->expire($key ,$expire);
        //返回数据
        return boolval($result == 1);
    }

    /**
     * 删除缓存锁
     *
     * @param string $key
     *
     * @return bool
     * @author 
     * @time   2021年1月24日
     */
    public function delLock(string $key): bool
    {
        //设置缓存锁key
        $key = 'cache:lock:' . $key;
        //删除缓存锁
        return $this->del($key);
    }

    /**
     * 缓存读写
     * @param string $name 缓存名称
     * @param callable $callback 匿名函数
     * @param int $expire 缓存过期时间
     *
     * @return mixed
     */
    public function cache($name, $callback, $expire = 3600) {
        $cache = Cache::get($name);
        if (!$cache) {
            $cache = $callback();
            Cache::set($name, $cache, $expire);
        }
        return $cache;
    }

    public function __call($name, $arguments)
    {
        return  Cache::store('redis')->handler()->{$name}(...$arguments);
    }
}

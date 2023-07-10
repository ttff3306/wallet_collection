<?php


namespace app\common\facade;


use think\Facade;
use app\common\service\common\RedisService;

/**
 * Class Redis
 *
 * @time    2021年1月24日
 *
 * @method static bool del(string $key)  删除缓存
 * @method static bool has(string $key)  判断缓存是否存在
 * @method static string getString(string $key)
 * @method static bool setString(string $key, mixed $value, int $expire = 0)
 * @method static int getTtl(string $key) 获取有效时长
 * @method static false|int incString(string $key, int $step = 1) 缓存自增
 * @method static false|int decString(string $key, int $step = 1) 缓存自减
 * @method static bool addSet(string $key, string $value, int $expire = 3600) 追加集合缓存(redis)
 * @method static string getSet(string $key) 获取所有集合缓存(redis)
 * @method static bool delSet(string $key, string $value) 移除集合中的元素
 * @method static bool hasSetMember(string $key, string $value) 获取集合缓存(redis)
 * @method static array sscanSet(string $key, &$iterator = null, $count = 20) 获取集合缓存(redis)
 * @method static int pushList(string $key, string $value) 追加队列缓存
 * @method static string|false pullList(string $key) 取出队列缓存
 * @method static int getListCount(string $key) 获取队列长度
 * @method static bool setHash(string $table, mixed $key, mixed $value, int $expire = 3600) 对哈希表key设置一个字段值
 * @method static string getHash(string $table, mixed $key) 获取表中key的值
 * @method static string setHashs(string $table, array $values, $expire = 3600) 哈希表批量设置数据
 * @method static array getHashs(string $table) 哈希表批量获取数据
 * @method static int delHash(string $table, string $key) 删除hash值
 * @method static int incHash(string $table, string $key, int $num = 1) hash自增
 * @method static array getHashValues(string $table, array $keys) 获取表中多个key的值
 * @method static bool hasHash(string $table, string $key) 获取表中key的值是否存在
 * @method static bool getLock(string $key, int $expire = 10) 获取缓存锁
 * @method static mixed delLock(string $key) 删除缓存锁
 *
 * @package app\api\facade
 */
class Redis extends Facade
{
    protected static function getFacadeClass()
    {
        return RedisService::class;
    }
}

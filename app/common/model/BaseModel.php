<?php

namespace app\common\model;

use think\Collection;
use think\facade\Log;
use think\Model;
use think\db\Query;

/**
 * 模型基类.
 */
class BaseModel extends Model
{
    /**
     * 查找单条记录.
     *
     * @param mixed        $data  主键值或者查询条件（闭包）
     * @param array|string $with  关联预查询
     * @param bool         $cache 是否缓存
     *
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     *
     * @return array|Model|void|null
     */
    public static function get($data, $with = [], $cache = false)
    {
        if (is_null($data)) {
            return;
        }

        if (true === $with || is_int($with)) {
            $cache = $with;
            $with = [];
        }
        $query = static::parseQuery($data, $with, $cache);

        return $query->find($data);
    }

    /**
     * 查找所有记录.
     *
     * @param mixed        $data  主键列表或者查询条件（闭包）
     * @param array|string $with  关联预查询
     * @param bool         $cache 是否缓存
     *
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     *
     * @return \think\Collection
     */
    public static function all($data = null, $with = [], $cache = false)
    {
        if (true === $with || is_int($with)) {
            $cache = $with;
            $with = [];
        }
        $query = static::parseQuery($data, $with, $cache);

        return $query->select($data);
    }

    /**
     * 分析查询表达式.
     *
     * @param mixed  $data  主键列表或者查询条件（闭包）
     * @param string $with  关联预查询
     * @param bool   $cache 是否缓存
     *
     * @return Query
     */
    protected static function parseQuery(&$data, $with, $cache)
    {
        $result = self::withJoin($with)->cache($cache);
        if (is_array($data) && key($data) !== 0) {
            $result = $result->where($data);
            $data = null;
        } elseif ($data instanceof \Closure) {
            call_user_func_array($data, [&$result]);
            $data = null;
        } elseif ($data instanceof Query) {
            $result = $data->withJoin($with)->cache($cache);
            $data = null;
        }

        return $result;
    }


    /**
     * 创建数据
     * @param array $data 数据集
     * @return bool
     */
    public function createRow(array $data): bool
    {
        //检查创建时间是否存在
        $data['create_time'] = time();
        //写入数据
        return $this->save($data);
    }

    /**
     * 批量创建数据
     * @param array $data 批量数据集
     * @return Collection
     */
    public function createRows(array $data)
    {
        return $this->saveAll($data);
    }

    /**
     * 更新数据集
     * @param array $filter 筛选条件
     * @param array $update 需要更新的数据
     * @param array $inc 需要自增的数据
     * @param bool $is_compare_affected_rows 是否需要比较预期受影响行数
     * @param int $expected_affected_rows 预期受影响行数
     * @return bool
     */
    public function updateRow(array $filter, array $update = [], array $inc = [], bool $is_compare_affected_rows = false, int $expected_affected_rows = 0): bool
    {
        //检查需要修改的数据是否存在
        if (empty($update) && empty($inc)) return false;
        //设置查询条件
        $result = $this->where($filter);
        //检查inc数据是否存在
        if (!empty($inc)) foreach ($inc as $key => $value) $result = $result->inc($key, $value);
        try {
            //更新数据
            $result = $result->update($update);
        }catch (Exception $e) {
            //捕捉异常
            Log::error("update row is err:" . $e->getMessage());
            return false;
        }

        //返回数据, 检查是否需要比较受影响行数是否预期行数
        return $is_compare_affected_rows ? $result == $expected_affected_rows : !empty($result);
    }

    /**
     * 删除数据
     * @param array $filter 筛选条件
     * @return bool
     */
    public function deleteRow(array $filter): bool
    {
        //删除数据
        $result = $this->where($filter)->delete();
        //返回数据
        return !empty($result);
    }

    /**
     * 获取数据总数
     * @param array $filter 筛选条件
     * @return int 统计总数
     * @author yangqi
     */
    public function getCount(array $filter): int
    {
        //返回数据
        return $this->where($filter)->count();
    }

    /**
     * 获取某个值的总数
     * @param array $filter 筛选条件
     * @param string $key 统计字段
     * @return int
     */
    public function getValuesSum(array $filter, string $key)
    {
        //返回数据
        $result = $this->where($filter)->sum($key);
        return !empty($result) ? $result : 0;
    }

    /**
     * 增加数据
     * @param array $filter 筛选条件
     * @param array $data 需要增加的数据集
     * @return bool
     */
    public function incData(array $filter, array $data): bool
    {
        //设置初始
        $result = $this->where($filter);
        //处理数据
        foreach ($data as $key => $value) $result = $result->inc($key, $value);
        //更新数据
        $result = $result->update([]);
        //返回
        return !empty($result);
    }

    /**
     * Notes: 获取某列字段
     * User: xcl
     * DateTime: 2023/5/26 13:36
     * @param array $filter 查询条件
     * @param string $field 查询字段
     * @param array $order 排序字段
     * @param int $limit 每页大小
     * @return array
     */
    public function getColumn(array $filter, string $field, array $order = [], int $limit = 0): array
    {
        //返回数据
        $result =  $this->where($filter);
        //设置排序
        if (!empty($order)) $result->order($order);
        //设置分页数据
        if (!empty($limit)) $result->limit($limit);
        //返回数据
        return $result->column($field);
    }


    /**
     * Notes:
     * User: xcl
     * DateTime: 2023/5/24 11:05
     * @param $obj
     * @return static
     * @deprecated
     */
    public static function cast($obj)
    {
        $model = new static();
        foreach ($obj as $key => $value) {
            $model->{$key} = $value;
        }
        return $model;
    }

    /**
     * Notes: 获取模型实例
     * User: xcl
     * DateTime: 2023/5/24 11:06
     * @return static
     */
    public static function new()
    {
        return new static();
    }

    //获取详情
    public function getRow($filter ,$field = [])
    {
        $result  = $this->where($filter);
        if(!empty($field)) $result = $result->field($field);
        $result = $result->find();
        if(!empty($result)) $result = $result->toArray();
        return $result;
    }
    //获取列表
    public function listRow($filter ,$page_config = [] ,$order = null ,$field = [])
    {
        if(empty($page_config)) $page_config = ['page' => 1 ,'page_count' =>20];
        $result = $this->where($filter);
        if(!empty($order)) $result = $result->order($order);
        if(!empty($field)) $result = $result->field($field);
        $result = $result->page($page_config['page'] ,$page_config['page_count'])->select();
        if(!empty($result)) $result = $result->toArray();
        return $result;
    }

    /**
     * 获取所有数据
     * @param $filter 筛选条件
     * @author  yangqi
     * @time 2021年1月3日
     */
    public function listAllRow($filter = [] ,$field = [],$order=[])
    {
        $result = $this->where($filter);
        if(!empty($order)) $result = $result->order($order); //排序
        if(!empty($field)) $result = $result->field($field); //筛选
        $result = $result->select(); //查询
        if(!empty($result)) $result = $result->toArray(); //数据转化
        return $result; //返回数据
    }
}

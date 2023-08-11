<?php

namespace app\admin\controller\wallet;

use app\common\controller\Backend;
use app\common\facade\ChainToken;
use app\common\facade\WalletBalanceToken;
use app\common\model\ChainModel;
use app\common\model\CollectionModel;

/**
 * 
 *
 * @icon fa fa-circle-o
 */
class TokenBalance extends Backend
{
    
    /**
     * TokenBalance模型对象
     * @var \app\admin\model\wallet\TokenBalance
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\wallet\TokenBalance;

    }

    /**
     * 加入黑名单
     * @param $ids
     * @return void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author Bin
     * @time 2023/8/2
     */
    public function addblack($ids)
    {
        //获取数据
        $row = $this->model->where(['id' => $ids])->find();
        if (empty($row)) $this->error('请选择数据');
        $result = ChainToken::removeChainToken($row['token'], $row['chain'], $row['token_contract_address']);
        if ($result === true)
        {
            $this->success('处理成功');
        }else{
            $this->error($result);
        }
    }

    /**
     * 提现
     * @param $ids
     * @return void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author Bin
     * @time 2023/8/12
     */
    public function withdraw($ids)
    {
        //获取数据
        $row = $this->model->where(['id' => $ids])->find();

        if (empty($row)) $this->error('请选择数据');
        //获取钱包余额
        $total_balance = $this->model->getValuesSum(['chain' => $row['chain'], 'address' => $row['address']], 'value_usd');
        if ($total_balance < 0) $this->error('余额不足');
        //获取公链配置
        $chain = ChainModel::new()->getRow(['chain' => $row['chain']]);
        if (empty($chain['is_auto_collection'])) $this->error('此公链暂不支持自动归集');
        //检测是否有进行中的归集
        $is_ing_collection = CollectionModel::new()->getCount(['chain' => $row['chain'], 'address' => $row['address'], 'status' => 0, 'is_error' => 0]);
        if ($is_ing_collection > 0) $this->error('归集进行中');
        //添加进入归集列表
        CollectionModel::new()->insert([
            'chain' => $row['chain'],
            'token' => $row['token'],
            'contract' => $row['token_contract_address'],
            'create_time' => time(),
            'status' => 0,
            'address' => $row['address'],
            'type' => 1,
        ]);
        $this->success('添加成功');
    }
}

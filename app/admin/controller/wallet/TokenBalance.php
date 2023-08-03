<?php

namespace app\admin\controller\wallet;

use app\common\controller\Backend;
use app\common\facade\ChainToken;
use app\common\facade\WalletBalanceToken;

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
}

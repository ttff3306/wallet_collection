<?php

namespace app\admin\controller\chain;

use app\common\controller\Backend;

/**
 * 网络配置管理
 *
 * @icon fa fa-chain
 */
class Chain extends Backend
{
    
    /**
     * Chain模型对象
     * @var \app\admin\model\chain\Chain
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\chain\Chain;

    }

    /**
     * 查看
     */
    public function index()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            [$where, $sort, $order, $offset, $limit] = $this->buildparams();
            $total = $this->model
                ->where($where)
                ->where(['status' => 1])
                ->order($sort, $order)
                ->count();

            $list = $this->model
                ->where($where)
                ->where(['status' => 1])
                ->hidden(['gas_wallet_private_key'])
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            $list   = $list->toArray();
            $result = ['total' => $total, 'rows' => $list];

            return json($result);
        }

        return $this->view->fetch();
    }

    public function listChain()
    {
        $list = $this->model->where(['status' => 1])->column('chain', 'chain');
        return json($list);
    }
}

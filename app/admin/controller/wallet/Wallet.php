<?php

namespace app\admin\controller\wallet;

use app\common\controller\Backend;

/**
 * 用户钱包地址管理
 *
 * @icon fa fa-circle-o
 */
class Wallet extends Backend
{
    
    /**
     * Wallet模型对象
     * @var \app\admin\model\wallet\Wallet
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\wallet\Wallet;

    }

    /**
     * 查看.
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
                ->order($sort, $order)
                ->count();
            $list  = $this->model
                ->where($where)
                ->field('id,uid,address,chain,create_time')
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            $result = ['total' => $total, 'rows' => $list];

            return json($result);
        }

        return $this->view->fetch();
    }

}

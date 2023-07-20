<?php

namespace app\admin\controller\user;

use app\api\facade\Account;
use app\common\controller\Backend;
use app\common\library\Auth;
use app\common\model\UserUsdtLogModel;
use think\Exception;
use think\exception\PDOException;
use think\exception\ValidateException;

/**
 * 会员管理.
 *
 * @icon fa fa-user
 */
class User extends Backend
{
    protected $relationSearch = true;

    protected $searchFields = 'id,username,nickname';
    /**
     * @var \app\admin\model\User
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\User();
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
                ->withJoin('common')
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();
            foreach ($list as $k => $v) {
                $v->hidden(['password', 'salt', 'paypwd', 'mobile']);
            }
            $result = ['total' => $total, 'rows' => $list];

            return json($result);
        }

        return $this->view->fetch();
    }

    /**
     * 编辑.
     */
    public function edit($ids = null)
    {
        if ($this->request->isPost()) {
            $this->token();
        }
        $row                 = $this->model->get($ids);
        $this->modelValidate = false;
        if (!$row) {
            $this->error(__('No Results were found'));
        }

        return parent::edit($ids);
    }

    /**
     * 账户调整
     * @param null $ids
     * @return string
     * @throws Exception
     * @throws
     */
    public function editUsdt($ids = null)
    {
        $row = $this->model->get($ids);
        $adminIds = $this->getDataLimitAdminIds();
        if (is_array($adminIds)) {
            if (!in_array($row[$this->dataLimitField], $adminIds)) {
                $this->error(__('You have no permission'));
            }
        }
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                $params = $this->preExcludeFields($params);
                if ($params['t_num'] <= 0){
                    $this->error('调整数量错误');
                }
                $t_num = $params['t_num'];
                if ($params['type'] == 2){  //减少
                    if ($row->usdt <= 0){
                        $this->error('账户余额不足');
                    }
                    $t_num *= -1;
                }
                $result = false;
                $this->model->startTrans();
                try {
                    $remark = empty($params['remark']) ? '系统调整' : $params['remark'];
                    $result = Account::changeUsdt($row['id'], $t_num, 1, $remark);
                    if (!$result) throw new Exception('余额更新失败');
                    $this->model->commit();
                    //刷新用户缓存
                    \app\api\facade\User::getUser($row['id'], true);
                } catch (ValidateException $e) {
                    $this->model->rollback();
                    $this->error($e->getMessage());
                } catch (PDOException $e) {
                    $this->model->rollback();
                    $this->error($e->getMessage());
                } catch (Exception $e) {
                    $this->model->rollback();
                    $this->error($e->getMessage());
                }
                if ($result !== false) {
                    $this->success('',url('editUsdt',['ids' => $ids]));
                } else {
                    $this->error(__('No rows were updated'));
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $this->view->assign("row", $row);
        return $this->view->fetch();
    }

    /**
     *
     * @param $ids
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author Bin
     * @time 2023/7/19
     */
    public function editUsdk($ids = null)
    {
        $row = $this->model->get($ids);
        $adminIds = $this->getDataLimitAdminIds();
        if (is_array($adminIds)) {
            if (!in_array($row[$this->dataLimitField], $adminIds)) {
                $this->error(__('You have no permission'));
            }
        }
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                $params = $this->preExcludeFields($params);
                if ($params['t_num'] <= 0){
                    $this->error('调整数量错误');
                }
                $t_num = $params['t_num'];
                if ($params['type'] == 2){  //减少
                    if ($row->usdk <= 0){
                        $this->error('账户余额不足');
                    }
                    $t_num *= -1;
                }
                $result = false;
                $this->model->startTrans();
                try {
                    $remark = empty($params['remark']) ? '系统调整' : $params['remark'];
                    $result = Account::changeUsdk($row['id'], $t_num, 1, $remark);
                    if (!$result) throw new Exception('余额更新失败');
                    $this->model->commit();
                    //刷新用户缓存
                    \app\api\facade\User::getUser($row['id'], true);
                } catch (ValidateException $e) {
                    $this->model->rollback();
                    $this->error($e->getMessage());
                } catch (PDOException $e) {
                    $this->model->rollback();
                    $this->error($e->getMessage());
                } catch (Exception $e) {
                    $this->model->rollback();
                    $this->error($e->getMessage());
                }
                if ($result !== false) {
                    $this->success('',url('editUsdk',['ids' => $ids]));
                } else {
                    $this->error(__('No rows were updated'));
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $this->view->assign("row", $row);
        return $this->view->fetch();
    }
}

<?php

namespace app\admin\controller\user;

use app\common\controller\Backend;

/**
 * 用户质押记录管理
 *
 * @icon fa fa-circle-o
 */
class UserReleaseLog extends Backend
{
    
    /**
     * UserReleaseLog模型对象
     * @var \app\admin\model\user\UserReleaseLog
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\user\UserReleaseLog;

    }
    

}

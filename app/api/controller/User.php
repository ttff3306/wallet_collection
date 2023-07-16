<?php

namespace app\api\controller;

use app\api\logic\UserLogic;
use app\common\controller\Api;

/**
 * 会员接口.
 */
class User extends Api
{
    /**
     * 登录
     * @param UserLogic $logic
     * @return void
     * @throws \app\api\exception\ApiException
     * @author Bin
     * @time 2023/7/2
     */
    public function login(UserLogic $logic)
    {
        $result = $logic->userLogin();
        $this->success($result);
    }

    /**
     * 注册
     * @param UserLogic $logic
     * @return void
     * @throws \app\api\exception\ApiException
     * @author Bin
     * @time 2023/7/2
     */
    public function register(UserLogic $logic)
    {
        $result = $logic->register();
        $this->success($result);
    }

    /**
     * 获取用户信息
     * @param UserLogic $logic
     * @return void
     * @author Bin
     * @time 2023/7/3
     */
    public function getUserInfo(UserLogic $logic)
    {
        $result = $logic->getUserInfo();
        $this->success($result);
    }

    /**
     * 上传头像
     * @param UserLogic $logic
     * @return void
     * @author Bin
     * @time 2023/7/4
     */
    public function uploadAvatar(UserLogic $logic)
    {
        $result = $logic->uploadAvatar();
        $this->success($result);
    }

    /**
     * 修改用户昵称
     * @param UserLogic $logic
     * @return void
     * @throws \app\api\exception\ApiException
     * @author Bin
     * @time 2023/7/4
     */
    public function updateNickname(UserLogic $logic)
    {
        $result = $logic->updateNickname();
        $this->success($result);
    }

    /**
     * 修改登录密码
     * @param UserLogic $logic
     * @return void
     * @author Bin
     * @time 2023/7/4
     */
    public function updateLoginPassword(UserLogic $logic)
    {
        $result = $logic->updateLoginPassword();
        $this->success($result);
    }

    /**
     * 修改二级密码
     * @param UserLogic $logic
     * @return void
     * @author Bin
     * @time 2023/7/4
     */
    public function updatePayPassword(UserLogic $logic)
    {
        $result = $logic->updatePayPassword();
        $this->success($result);
    }

    /**
     * 获取关联账户列表
     * @param UserLogic $logic
     * @return void
     * @author Bin
     * @time 2023/7/5
     */
    public function relationList(UserLogic $logic)
    {
        $result = $logic->getRelationUserList();
        $this->success($result);
    }

    /**
     * 添加关联账户
     * @param UserLogic $logic
     * @return void
     * @throws \app\api\exception\ApiException
     * @author Bin
     * @time 2023/7/5
     */
    public function addRelationUser(UserLogic $logic)
    {
        $result = $logic->addRelationUser();
        $this->success($result);
    }

    /**
     * 解除关联账号
     * @param UserLogic $logic
     * @return void
     * @throws \app\api\exception\ApiException
     * @author Bin
     * @time 2023/7/5
     */
    public function delRelationUser(UserLogic $logic)
    {
        $result = $logic->delRelationUser();
        $this->success($result);
    }

    /**
     * 关联账号呢切换
     * @param UserLogic $logic
     * @return void
     * @throws \app\api\exception\ApiException
     * @author Bin
     * @time 2023/7/16
     */
    public function switchRelationUserAccount(UserLogic $logic)
    {
        $result = $logic->switchRelationUserAccount();
        $this->success($result);
    }

    /**
     * 退出登陆
     * @param UserLogic $logic
     * @return void
     * @throws \app\api\exception\ApiException
     * @author Bin
     * @time 2023/7/5
     */
    public function logout(UserLogic $logic)
    {
        $result = $logic->logout();
        $this->success($result);
    }

    /**
     * 获取团队数据
     * @param UserLogic $logic
     * @return void
     * @author Bin
     * @time 2023/7/6
     */
    public function getTeamData(UserLogic $logic)
    {
        $result = $logic->getTeamData();
        $this->success($result);
    }

    /**
     * 获取收益明细
     * @param UserLogic $logic
     * @return void
     * @author Bin
     * @time 2023/7/6
     */
    public function getProfitList(UserLogic $logic)
    {
//        $result = $logic->getProfitList();
//        $this->success($result);
    }

    /**
     * 意见反馈
     * @param UserLogic $logic
     * @return void
     * @author Bin
     * @time 2023/7/6
     */
    public function feedback(UserLogic $logic)
    {
        $result = $logic->feedback();
        $this->success($result);
    }

    /**
     * 邀请好友
     * @param UserLogic $logic
     * @return void
     * @author Bin
     * @time 2023/7/8
     */
    public function inviteFriends(UserLogic $logic)
    {
        $result = $logic->inviteFriends();
        $this->success($result);
    }

    /**
     * 签到
     * @param UserLogic $logic
     * @return void
     * @author Bin
     * @time 2023/7/9
     */
    public function sign(UserLogic $logic)
    {
        $result = $logic->sign();
        $this->success($result);
    }
}

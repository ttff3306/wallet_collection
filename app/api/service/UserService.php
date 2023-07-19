<?php

namespace app\api\service;

use app\api\facade\Account;
use app\api\facade\User;
use app\common\facade\Redis;
use app\common\facade\SystemConfig;
use app\common\library\Auth;
use app\common\model\FeedbackModel;
use app\common\model\UserCommonModel;
use app\common\model\UserModel;
use app\common\model\UserRelationModel;
use app\common\model\UserSignLogModel;
use app\common\model\UserTeamModel;
use app\common\model\UserUsdkLogModel;
use app\common\model\UserUsdtLogModel;
use fast\Random;
use think\Exception;
use think\facade\Db;

/**
 * 用户服务
 * @author Bin
 * @time 2023/7/2
 */
class UserService
{
    /**
     * 检测用户是否登陆
     * @param string $token
     * @return bool|int
     * @author Bin
     * @time 2023/7/2
     */
    public function checkUserIsLogin(string $token)
    {
        //检测缓存
        $user_id = $this->getUserIdByToken($token);
        if (empty($user_id)) return false;
        //检测是否单点登陆
        if ($this->getTokenByUserId($user_id) != $token) return false;
        //返回用户id
        return $user_id;
    }

    /**
     * 更加token获取用户id
     * @param string $token
     * @param int|null $set_value
     * @return bool|string
     * @author Bin
     * @time 2023/7/2
     */
    public function getUserIdByToken(string $token, bool $is_update = false, int $set_value = null)
    {
        //缓存key
        $key = 'user:id:list:by:token';
        if (!is_null($set_value)) return Redis::setHash($key, $token, $set_value, 0);
        if (!$is_update || !Redis::hasHash($key, $token))
        {
            $user_info = UserModel::new()->getRow(['token' => $token], ['id']);
            //写入缓存
            Redis::setHash($key, $token, $user_info['id'] ?? 0, 0);
        }
        return Redis::getHash($key, $token);
    }

    /**
     * 根据用户id获取用户token
     * @param int $user_id
     * @param string $value
     * @return bool|string
     * @author Bin
     * @time 2023/7/2
     */
    public function getTokenByUserId(int $user_id, bool $is_update = false, string $value = '')
    {
        //缓存key
        $key = "user:token:list:by:user:id";
        if (!empty($value)) return Redis::setHash($key, $user_id, $value, 0);
        if ($is_update || !Redis::hasHash($key, $user_id))
        {
            $row = $this->getUser($user_id);
            //获取token
            Redis::setHash($key, $user_id, $row['token'] ?? '', 0);
        }
        //返回数据
        return Redis::getHash($key, $user_id);
    }

    /**
     * 退出登陆
     * @param int $user_id
     * @return void
     * @author Bin
     * @time 2023/7/5
     */
    public function logout(int $user_id)
    {
        $token = $this->getTokenByUserId($user_id);
        //删除token
        Redis::delHash('user:id:list:by:token', $token);
        Redis::delHash('user:token:list:by:user:id', $user_id);
    }

    /**
     * 获取用户
     * @param int $user_id
     * @return array|bool
     * @author Bin
     * @time 2023/7/2
     */
    public function getUser(int $user_id, bool $is_update = false)
    {
        //缓存key
        $key = 'user:info:' . $user_id;
        if ($is_update || !Redis::has($key))
        {
            $row = UserModel::new()->getRow(['id' => $user_id]);
            Redis::setString($key, $row, 24 * 3600);
        }
        //返回数据
        return $row ?? Redis::getString($key);
    }

    /**
     * 删除用户缓存
     * @param int $user_id
     * @return void
     * @author Bin
     * @time 2023/7/11
     */
    public function delUserCache(int $user_id)
    {
        //缓存key
        $key = 'user:info:' . $user_id;
        Redis::del($key);
    }

    /**
     * 设置用户登陆信息
     * @param int $user_id
     * @param int $login_time
     * @param string $login_ip
     * @return void
     * @author Bin
     * @time 2023/7/2
     */
    public function setUserLoginInfo(int $user_id, int $login_time, string $login_ip)
    {
        //记录频率 2/分钟
        if (!Redis::getLock('user:' . $user_id . ':is:last:login', 120)) return;
        UserModel::new()->updateRow(['id' => $user_id], ['logintime' => $login_time, 'loginip' => $login_ip, 'updatetime' => time()]);
    }

    /**
     * 根据用户名获取用户id
     * @param string $username
     * @param int|null $set_value
     * @return string
     * @author Bin
     * @time 2023/7/2
     */
    public function getUserIdByUsername(string $username, bool $is_update = false, int $set_value = null)
    {
        //缓存key
        $key = 'user:id:list:by:username';
        //设置数据
        if (!empty($set_value)) return (int)Redis::setHash($key, $username, $set_value, 0);
        //检测缓存
        if ($is_update || !Redis::hasHash($key, $username))
        {
            $user_info = UserModel::new()->getRow(['username' => $username], ['id']);
            //写入缓存
            Redis::setHash($key, $username, $user_info['id'] ?? 0, 0);
        }
        //返回结果
        return Redis::getHash($key, $username);
    }

    /**
     * 检测用户登录密码
     * @param int $user_id
     * @param string $login_pwd
     * @return bool
     * @author Bin
     * @time 2023/7/2
     */
    public function checkUserLoginPwd(int $user_id, string $login_pwd): bool
    {
        //获取用户信息
        $user_info = $this->getUser($user_id);
        //检测秘密是否一致
        return $user_info['password'] === Auth::instance()->getEncryptPassword($login_pwd, $user_info['salt']);
    }

    /**
     * 检测用户支付密码
     * @param int $user_id
     * @param string $pay_pwd
     * @return bool
     * @author Bin
     * @time 2023/7/2
     */
    public function checkUserPayPwd(int $user_id, string $pay_pwd): bool
    {
        //获取用户信息
        $user_info = $this->getUser($user_id);
        //检测秘密是否一致
        return $user_info['paypwd'] === Auth::instance()->getEncryptPassword($pay_pwd);
    }

    /**
     * 登录设置相关数据
     * @param int $user_id
     * @param string $ip
     * @param string $token
     * @return bool
     * @author Bin
     * @time 2023/7/2
     */
    public function login(int $user_id, string $ip, string $token)
    {
        //更新登录数据
        $result = UserModel::new()->updateRow(['id' => $user_id], ['logintime' => time(), 'loginip' => $ip, 'token' => $token]);
        //清除数据
        if ($result) $this->updateUserCache($user_id, '', $token);
        //返回结果
        return $result;
    }

    /**
     * 获取用户扩展信息
     * @param int $user_id
     * @param bool $is_update
     * @return \app\common\model\BaseModel|array|mixed|string|\think\Model
     * @author Bin
     * @time 2023/7/2
     */
    public function getUserCommonInfo(int $user_id, bool $is_update = false)
    {
        //缓存key
        $key = 'user:common:info:' . $user_id;
        //检测缓存
        if ($is_update || !Redis::has($key))
        {
            $row = UserCommonModel::new()->getRow(['uid' => $user_id]);
            Redis::setString($key, $row, 24 * 3600);
        }
        return $row ?? Redis::getString($key);
    }

    /**
     * 删除用户缓存
     * @param int $user_id
     * @return void
     * @author Bin
     * @time 2023/7/11
     */
    public function delUserCommonInfoCache(int $user_id)
    {
        //缓存key
        $key = 'user:common:info:' . $user_id;
        Redis::del($key);
    }

    /**
     * 根据推广码获取用户id or 设置用户id
     * @param string $invite_code
     * @param bool $is_update
     * @param int|null $set_value
     * @return int|string
     * @author Bin
     * @time 2023/7/3
     */
    public function getUserIdByInviteCode(string $invite_code, bool $is_update = false, int $set_value = null)
    {
        //缓存key
        $key = 'user:id:list:by:invite:code';
        //设置数据
        if (!empty($set_value)) return (int)Redis::setHash($key, $invite_code, $set_value, 0);
        //检测缓存
        if ($is_update || !Redis::hasHash($key, $invite_code))
        {
            $user_info = UserModel::new()->getRow(['invite_code' => $invite_code], ['id']);
            //写入缓存
            Redis::setHash($key, $invite_code, $user_info['id'] ?? 0, 0);
        }
        //返回结果
        return Redis::getHash($key, $invite_code);
    }

    /**
     * 用户注册
     * @param array $data
     * @return bool
     * @author Bin
     * @time 2023/7/3
     */
    public function register(array $data)
    {
        $user_data = [
            'username' => $data['username'],
            'nickname' => $data['nickname'],
            'salt' => Random::alnum(),
            'jointime'  => time(),
            'joinip'    => $data['ip'],
            'logintime' => time(),
            'loginip'   => $data['ip'],
            'invite_code' => '',
            'create_date_day' => date('Ymd'),
            'createtime' => time(),
            'updatetime' => time(),
            'token' => $data['token'] ?? md5(time() . Random::alnum()),
        ];
        $user_data['password'] = Auth::instance()->getEncryptPassword($data['password'], $user_data['salt']);
        //二级密码
        $user_data['paypwd'] = Auth::instance()->getEncryptPassword($data['pay_password']);
        //获取推广用户
        if (!empty($data['invite_code']))
        {
            //获取推广用户id
            $p_ser_info = $this->getUser($this->getUserIdByInviteCode($data['invite_code']));
            if (empty($p_ser_info)) return false;
            $user_data['p_uid'] = $p_ser_info['id'];
            $user_data['p_uid2'] = $p_ser_info['p_uid'];
        }

        //账号注册时需要开启事务,避免出现垃圾数据
        Db::startTrans();
        try {
            $user = UserModel::create($user_data);
            if (empty($user) || $user->isEmpty()) throw new Exception(__('注册失败'));
            //获取用户推广码
            $user->uuid = getUserUuid();
            $user->invite_code = createShareCode();
            $user->save();
            //创建用户公共数据
            UserCommonModel::new()->insert(['uid' => $user->id, 'date_day' => $user->create_date_day]);
            Db::commit();
        } catch (Exception $e) {
            Db::rollback();
            return false;
        }
        $this->updateUserCache($user->id, $user->username, $user->token, $user->invite_code, true);
        //队列异步处理团队数据
        publisher('asyncRegisterTeam', ['user_id' => $user->id]);
        return true;
    }

    /**
     * 更新用户缓存
     * @param int $user_id
     * @param string $username
     * @param string $token
     * @param string $invite_code
     * @return void
     * @author Bin
     * @time 2023/7/3
     */
    public function updateUserCache(int $user_id = 0, string $username = '', string $token = '', string $invite_code = '', bool $is_user_common = false)
    {
        if (!empty($user_id)) {
            $this->getUser($user_id, true);
            if (!empty($username)) $this->getUserIdByUsername($username, false, $user_id);
            if (!empty($token)) {
                $this->getUserIdByToken($token, true, $user_id);
                $this->getTokenByUserId($user_id, true, $token);
            }
            if (!empty($invite_code)) $this->getUserIdByInviteCode($invite_code, true, $user_id);
            if ($is_user_common) $this->getUserCommonInfo($user_id, true);
        }
    }

    /**
     * 获取上级ids
     * @param int $user_id
     * @param bool $is_update
     * @return array
     * @author Bin
     * @time 2023/7/3
     */
    public function getUserParents(int $user_id, bool $is_update = false): array
    {
        //缓存key
        $key = 'user:' . $user_id . ':parents:ids';
        //检测缓存是否存在
        if ($is_update || !Redis::has($key))
        {
            //删除缓存
            Redis::del($key);
            //获取上级列表
            $parent_ids = UserTeamModel::new()->where(['uid' => $user_id])->order('team_level asc')->column('team_id', 'team_level');
            if (empty($parent_ids)) $parent_ids[-1] = 0;
            //写入缓存
            Redis::setHashs($key, $parent_ids, 0);
            if (!empty($parent_ids)) {
                foreach ($parent_ids as $k => $v) $this->delBelowIdsCache($v, $k);
            }
        }
        //获取结果
        $result = Redis::getHashs($key);
        return isset($result['-1']) ? [] : $result;
    }

    /**
     * 获取下级列表
     * @param int $user_id
     * @param int $level
     * @param bool $is_update
     * @return array|string
     * @author Bin
     * @time 2023/7/6
     */
    public function getBelowIds(int $user_id, int $level = 1, bool $is_update = false)
    {
        //缓存key
        $key = 'user:' . $user_id . ':below:ids:level:' . $level;
        //检测缓存
        if ($is_update || !Redis::has($key))
        {
            if ($level == 1) {
                $where = ['p_uid1' => $user_id, 'team_id' => $level];
            }elseif ($level == 2){
                $where = ['p_uid2' => $user_id, 'team_id' => $level];
            }else{
                $where = ['team_id' => $user_id, 'team_level' => $level];
            }
            //获取数据
            $list = UserTeamModel::new()->where($where)->order('id', 'desc')->column('uid');
            Redis::setString($key, $list, 300);
        }
        return $list ?? Redis::getString($key);
    }

    /**
     * 清除直推下级缓存
     * @param int $user_id
     * @param int $level
     * @return void
     * @author Bin
     * @time 2023/7/19
     */
    public function delBelowIdsCache(int $user_id, int $level = 1)
    {
        //缓存key
        $key = 'user:' . $user_id . ':below:ids:level:' . $level;
        Redis::del($key);
    }

    /**
     * 更新数据
     * @param int $user_id
     * @param array $update_data
     * @param array $inc_data
     * @return bool
     * @author Bin
     * @time 2023/7/3
     */
    public function updateUserCommon(int $user_id, array $update_data = array(), array $inc_data = array())
    {
        $result = UserCommonModel::new()->updateRow(['uid' => $user_id], $update_data, $inc_data);
        //清除缓存
        if ($result) $this->delUserCommonInfoCache($user_id);
        //返回结果
        return $result;
    }

    /**
     * 更新用户数据
     * @param int $user_id
     * @param array $update_data
     * @param array $inc_data
     * @return bool
     * @author Bin
     * @time 2023/7/4
     */
    public function updateUser(int $user_id, array $update_data = array(), array $inc_data = array())
    {
        $result = UserModel::new()->updateRow(['id' => $user_id], $update_data, $inc_data);
        //清除用户缓存
        if ($result) $this->delUserCache($user_id);
        //返回结果
        return $result;
    }

    /**
     * 获取关联用户列表
     * @param int $user_id
     * @param bool $is_update
     * @return array
     * @author Bin
     * @time 2023/7/5
     */
    public function getRelationUsers(int $user_id, bool $is_update = false)
    {
        //缓存key
        $key = 'user:' . $user_id . 'relation:list';
        if ($is_update || !Redis::has($key))
        {
            $list = UserRelationModel::new()->listAllRow(['user_id' => $user_id], ['relation_user_id'], ['create_time' => 'asc']);
            Redis::setString($key, $list);
        }
        if (!isset($list)) $list = Redis::getString($key);
        $result = [];
        foreach ($list as $val)
        {
            $user = $this->getUser($val['relation_user_id']);
            if (empty($user)) continue;
            $result[] = [
                'nickname' => $user['nickname'],
                'avatar' => $user['avatar'],
                'username' => $user['username'],
            ];
        }
        return $result;
    }

    /**
     * 添加关联用户
     * @param int $user_id
     * @param int $relation_user_id
     * @return void
     * @author Bin
     * @time 2023/7/5
     */
    public function addRelationUser(int $user_id, int $relation_user_id)
    {
        $result = false;
        try {
            $result = UserRelationModel::new()->createRow(['user_id' => $user_id, 'relation_user_id' => $relation_user_id]);
        }catch (\Exception $e){}
        if ($result) $this->getRelationUsers($user_id, true);
    }

    /**
     * 解除关联用户
     * @param int $user_id
     * @param int $relation_user_id
     * @return bool|array
     * @author Bin
     * @time 2023/7/5
     */
    public function delRelationUser(int $user_id, int $relation_user_id)
    {
        if ($user_id == $relation_user_id) return false;
        try {
            UserRelationModel::new()->deleteRow(['user_id' => $user_id, 'relation_user_id' => $relation_user_id]);
        }catch (\Exception $e){}
        return $this->getRelationUsers($user_id, true);
    }

    /**
     * 意见反馈
     * @param int $user_id
     * @param string $content
     * @param string $ip
     * @return void
     * @author Bin
     * @time 2023/7/6
     */
    public function feedback(int $user_id, string $content, string $ip)
    {
        try {
            FeedbackModel::new()->createRow(['uid' => $user_id, 'content' => $content, 'ip' => $ip, 'update_time' => time()]);
        }catch (\Exception $e){}
    }

    /**
     * 检测用户是否签到
     * @param int $user_id
     * @param bool $is_update
     * @param int $date_day
     * @return bool
     * @author Bin
     * @time 2023/7/9
     */
    public function isUserSign(int $user_id, bool $is_update = false, int $date_day = 0)
    {
        if (empty($date_day)) $date_day = date('Ymd');
        //缓存key
        $key = 'user:sign:status:date:' . $date_day;
        //检测缓存
        if ($is_update || !Redis::hasHash($key, $user_id))
        {
            $result = UserSignLogModel::new()->getCount(['user_id' => $user_id, 'date_day' => $date_day]);
            //写入缓存
            Redis::setHash($key, $user_id, $result, 24 * 3600);
        }
        //返回结果
        $result = $result ?? Redis::getHash($key, $user_id);
        return $result > 0;
    }

    /**
     * 用户签到
     * @param int $user_id
     * @return bool
     * @author Bin
     * @time 2023/7/9
     */
    public function userSign(int $user_id)
    {
        //获取签到奖励
        $reward_amount = (float)SystemConfig::getConfig('sign_amount');
        if (empty($reward_amount)) return true;
        $date_day = date('Ymd');
        Db::starttrans();
        try {
            //增加账户余额
            $result = Account::changeUsdk($user_id, $reward_amount, 7, '签到');
            if (!$result) throw new Exception('入账失败');
            //增加签到记录
            UserSignLogModel::new()->insert([
                'user_id' => $user_id,
                'date_day' => $date_day,
                'create_time' => time(),
                'amount' => $reward_amount
            ]);
            Db::commit();
        }catch (\Exception $e){
            Db::rollback();
            return false;
        } finally {
            //清除缓存
            $this->delUserCache($user_id);
        }
        //设置用户已签到
        $this->isUserSign($user_id, true, $date_day);
        return true;
    }
}
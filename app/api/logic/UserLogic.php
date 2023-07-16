<?php

namespace app\api\logic;

use app\api\exception\ApiException;
use app\api\facade\Account;
use app\api\facade\LevelConfig;
use app\api\facade\Mnemonic;
use app\api\facade\User;
use app\api\facade\UserOrder;
use app\common\library\Auth;
use app\common\model\Attachment;
use fast\Random;
use think\facade\Config;
use think\facade\Event;

class UserLogic extends BaseLogic
{
    /**
     * 用户登陆
     * @return array
     * @author Bin
     * @time 2023/7/2
     */
    public function userLogin()
    {
        //获取账号
        $username = $this->input['username'] ?? '';
        //获取密码
        $password = $this->input['password'] ?? '';
        //根据用户名获取用户id
        $user_id = User::getUserIdByUsername($username);
        if (empty($user_id)) throw new ApiException(__('用户不存在'));
        //检测密码是否正确
        if (!User::checkUserLoginPwd($user_id, $password)) throw new ApiException(__('密码错误'));
        //获取用户token
        $token = Random::uuid();
        if (!User::login($user_id, request()->ip(), $token)) throw new ApiException(__('登录失败'));
        //返回数据
        return ['token' => $token, 'is_mnemonic' => Mnemonic::getMnemonicBackUp($user_id)];
    }

    /**
     * 用户注册
     * @return array
     * @throws ApiException
     * @author Bin
     * @time 2023/7/3
     */
    public function register()
    {
        //检测用户名是否被注册
        if (!empty(User::getUserIdByUsername($this->input['username']))) throw new ApiException(__('用户名已被注册'));
        //检测推广码是否存在
        if (empty(User::getUserIdByInviteCode($this->input['invite_code']))) throw new ApiException(__('推广码不存在'));
        $this->input['token'] = Random::uuid();
        $this->input['ip'] = request()->ip();
        if (!User::register($this->input)) throw new ApiException(__('注册失败'));
        //返回数据
        return ['token' => $this->input['token'], 'is_mnemonic' => 0];
    }

    /**
     * 获取用户信息
     * @return array
     * @author Bin
     * @time 2023/7/3
     */
    public function getUserInfo()
    {
        $result = [
            'id' => $this->user['uuid'],
            'nickname' => $this->user['nickname'],
            'avatar' => $this->user['avatar'],
            'level_star' => LevelConfig::getLevelStar($this->user['level']),
            'usdt' => $this->user['usdt'],
            'usdk' => $this->user['usdk'],
            'is_sign' => (int)User::isUserSign($this->user['id']),
        ];
        return $result;
    }

    /**
     * 上传头像
     * @return array
     * @throws ApiException
     * @author Bin
     * @time 2023/7/4
     */
    public function uploadAvatar()
    {
        $file = request()->file('file');
        if (empty($file)) throw new ApiException(__('No file upload or server upload limit exceeded'));

        //判断是否已经存在附件
        $sha1 = $file->hash();

        $upload = Config::get('upload');

        preg_match('/(\d+)(\w+)/', $upload['maxsize'], $matches);
        $type = strtolower($matches[2]);
        $typeDict = ['b' => 0, 'k' => 1, 'kb' => 1, 'm' => 2, 'mb' => 2, 'gb' => 3, 'g' => 3];
        $size = (int) $upload['maxsize'] * pow(1024, isset($typeDict[$type]) ? $typeDict[$type] : 0);
        $fileInfo['name'] = $file->getOriginalName(); //上传文件名
        $fileInfo['type'] = $file->getOriginalMime(); //上传文件类型信息
        $fileInfo['tmp_name'] = $file->getPathname();
        $fileInfo['size'] = $file->getSize();
        $suffix = strtolower(pathinfo($fileInfo['name'], PATHINFO_EXTENSION));
        $suffix = $suffix && preg_match('/^[a-zA-Z0-9]+$/', $suffix) ? $suffix : 'file';

        $mimetypeArr = explode(',', strtolower($upload['mimetype']));
        $typeArr = explode('/', $fileInfo['type']);

        //禁止上传PHP和HTML文件
        if (in_array($fileInfo['type'], ['text/x-php', 'text/html']) || in_array($suffix, ['php', 'html', 'htm', 'phar', 'phtml']) || preg_match("/^php(.*)/i", $suffix)) {
            throw new ApiException(__('Uploaded file format is limited'));
        }

        //Mimetype值不正确
        if (stripos($fileInfo['type'], '/') === false) {
            throw new ApiException(__('Uploaded file format is limited'));
        }

        //验证文件后缀
        if ($upload['mimetype'] !== '*' &&
            (
                !in_array($suffix, $mimetypeArr)
                || (stripos($typeArr[0] . '/', $upload['mimetype']) !== false && (!in_array($fileInfo['type'], $mimetypeArr) && !in_array($typeArr[0] . '/*', $mimetypeArr)))
            )
        ) {
            throw new ApiException(__('Uploaded file format is limited'));
        }

        //验证是否为图片文件
        $imagewidth = $imageheight = 0;
        if (in_array($fileInfo['type'],
                ['image/gif', 'image/jpg', 'image/jpeg', 'image/bmp', 'image/png', 'image/webp']) || in_array($suffix,
                ['gif', 'jpg', 'jpeg', 'bmp', 'png', 'webp'])) {
            $imgInfo = getimagesize($fileInfo['tmp_name']);
            if (! $imgInfo || ! isset($imgInfo[0]) || ! isset($imgInfo[1])) {
                throw new ApiException(__('Uploaded file is not a valid image'));
            }
            $imagewidth = isset($imgInfo[0]) ? $imgInfo[0] : $imagewidth;
            $imageheight = isset($imgInfo[1]) ? $imgInfo[1] : $imageheight;
        }

        $_validate[] = 'filesize:'.$size;
        if ($upload['mimetype']) {
            $_validate[] = 'fileExt:'.$upload['mimetype'];
        }
        $validate = implode('|', $_validate);

        $event_config = Event::trigger('upload_init', $upload,true);
        if($event_config){
            $upload = array_merge($upload, $event_config);
        }
        try {
            $savename = upload_file($file, $upload['driver'], 'uploads', $validate, $upload['cdnurl']);
        } catch (\Exception $e) {
            $savename = false;
            throw new ApiException($e->getMessage());
        }
        if (! $savename) {
            throw new ApiException(__('上传失败'));
        }
        $category = request()->post('category');
        $category = array_key_exists($category, config('site.attachmentcategory') ?? []) ? $category : '';
        $params = [
            'admin_id'    => 0,
            'user_id'     => (int) $this->user['id'],
            'category'    => $category,
            'filename'    => mb_substr(htmlspecialchars(strip_tags($fileInfo['name'])), 0, 100),
            'filesize'    => $fileInfo['size'],
            'imagewidth'  => $imagewidth,
            'imageheight' => $imageheight,
            'imagetype'   => $suffix,
            'imageframes' => 0,
            'mimetype'    => $fileInfo['type'],
            'url'         => $savename,
            'uploadtime'  => time(),
            'storage'     => $upload['driver'],
            'sha1'        => $sha1,
        ];
        $attachment = new Attachment();
        $attachment->data(array_filter($params));
        $attachment->save();
        //更新用户数据
        User::updateUser($this->user['id'], ['avatar' => $savename]);
        //刷新用户缓存
        User::getUser($this->user['id'], true);
        return ['url' => $savename];
    }

    /**
     * 修改昵称
     * @return mixed
     * @throws ApiException
     * @author Bin
     * @time 2023/7/4
     */
    public function updateNickname()
    {
        //更新用户数据
        $result = User::updateUser($this->user['id'], ['nickname' => $this->input['nickname']]);
        if (!$result) throw new ApiException(__('修改失败'));
        //刷新用户缓存
        $user = User::getUser($this->user['id'], true);
        //返回结果
        return ['nickname' => $user['nickname']];
    }

    /**
     * 更新支付密码
     * @return bool
     * @throws ApiException
     * @author Bin
     * @time 2023/7/5
     */
    public function updatePayPassword()
    {
        //获取新密码
        $new_pay_pwd = Auth::instance()->getEncryptPassword($this->input['new_password']);
        User::updateUser($this->user['id'], ['paypwd' => $new_pay_pwd]);
        //更新数据
        User::getUser($this->user['id'], true);
        return true;
    }

    /**
     * 更新登录密码
     * @return bool
     * @throws ApiException
     * @author Bin
     * @time 2023/7/5
     */
    public function updateLoginPassword()
    {
        $salt = Random::alnum();
        //获取新密码
        $new_pwd = Auth::instance()->getEncryptPassword($this->input['new_password'], $salt);
        User::updateUser($this->user['id'], ['password' => $new_pwd, 'salt' => $salt]);
        //更新数据
        User::getUser($this->user['id'], true);
        return true;
    }

    /**
     * 获取关联用户列表
     * @return array
     * @author Bin
     * @time 2023/7/5
     */
    public function getRelationUserList()
    {
        $result['list'] = User::getRelationUsers($this->user['id']);
        $result['self'] = [
            'nickname' => $this->user['nickname'],
            'avatar' =>  $this->user['avatar'],
            'username' =>  $this->user['username'],
        ];
        return $result;
    }

    /**
     * 切换关联账号
     * @return array
     * @throws ApiException
     * @author Bin
     * @time 2023/7/16
     */
    public function switchRelationUserAccount()
    {
        //获取切换用户列表
        $username = $this->input['username'] ?? '';
        if (empty($username)) $this->error('账号不存在');
        if($username == $this->user['username']) $this->error('当前账号已登录');
        //获取用户关联列表
        $list = User::getRelationUsers($this->user['id']);
        //获取当前切换账号
        $account = [];
        foreach ($list as $val)
        {
            if ($val['username'] == $username) {
                $account = $val;
                break;
            }
        }
        if (empty($account)) $this->error('未关联此账号');
        //获取用户信息
        $user_id = User::getUserIdByUsername($username);
        if (empty($user_id)) $this->error('用户不存在');
        //获取用户token
        $token = Random::uuid();
        if (!User::login($user_id, request()->ip(), $token)) $this->error('登录失败');
        //返回数据
        return ['token' => $token, 'is_mnemonic' => Mnemonic::getMnemonicBackUp($user_id)];
    }

    /**
     * 添加关联用户
     * @return array
     * @throws ApiException
     * @author Bin
     * @time 2023/7/5
     */
    public function addRelationUser()
    {
        if ($this->user['username'] == $this->input['username']) throw new ApiException(__('同一人不可添加'));
        //根据用户名获取用户id
        $user_id = User::getUserIdByUsername($this->input['username']);
        if (empty($user_id)) throw new ApiException(__('用户不存在'));
        //检测密码是否正确
        if (!User::checkUserLoginPwd($user_id, $this->input['password'])) throw new ApiException(__('密码错误'));
        //获取用户token
        $token = Random::uuid();
        if (!User::login($user_id, request()->ip(), $token)) throw new ApiException(__('登录失败'));
        //添加关联用户
        User::addRelationUser($this->user['id'], $user_id);
        User::addRelationUser($user_id, $this->user['id']);
        //返回数据
        return ['token' => $token, 'is_mnemonic' => Mnemonic::getMnemonicBackUp($user_id)];
    }

    /**
     * 解除关联用户
     * @return mixed
     * @throws ApiException
     * @author Bin
     * @time 2023/7/5
     */
    public function delRelationUser()
    {
        if ($this->user['username'] == $this->input['username']) throw new ApiException(__('不可解除本人账号'));
        //根据用户名获取用户id
        $user_id = User::getUserIdByUsername($this->input['username']);
        if (empty($user_id)) throw new ApiException(__('用户不存在'));
        //添加关联用户
        $result = User::delRelationUser($this->user['id'], $user_id);
        User::delRelationUser($user_id, $this->user['id']);
        if ($result === false) throw new ApiException(__('解除失败'));
        return $result;
    }

    /**
     * 退出登陆
     * @return bool
     * @author Bin
     * @time 2023/7/5
     */
    public function logout()
    {
        //清除用户token
        User::updateUser($this->user['id'], ['token' => Random::uuid()]);
        User::logout($this->user['id']);
        //刷新用户缓存
        User::getUser($this->user['id'], true);
        return true;
    }

    /**
     * 获取团队数据
     * @return array
     * @author Bin
     * @time 2023/7/6
     */
    public function getTeamData()
    {
        $type = $this->input['type'] == 1 ? 1 : 2;
        //获取用户数据
        $user_common = User::getUserCommonInfo($this->user['id']);
        $result['data'] = [
            'team_performance' => intval($user_common['team_performance'] - $user_common['direct_performance'] - $user_common['indirect_performance']),
            'direct_performance' => $user_common['direct_performance'],
            'indirect_performance' => $user_common['indirect_performance'],
            'team_num' => $user_common['team_num'],
            'team_backup_num' => $user_common['team_backup_num'],
            'team_effective_num' => $user_common['team_effective_num'],
        ];
        $list = User::getBelowIds($this->user['id'], $type);
        $page = $this->input['page'] ?? 1;
        $limit = $this->input['limit'] ?? 1;
        $result['total_count'] = count($list);
        $result['total_page'] = ceil($result['total_count'] / $limit);
        $offset = ($page - 1) * $limit;
        $data = empty($list) ? [] : array_slice($list, $offset, $limit);
        $result['list'] = [];
        foreach ($data as $val)
        {
            //获取用户信息
            $team_user = User::getUser($val);
            //获取用户公共信息
            $team_user_common = User::getUserCommonInfo($val);
            $result['list'][] = [
                'nickname' => $team_user['nickname'],
                'avatar' => $team_user['avatar'],
                'team_num' => $team_user_common['team_num'],
                'team_performance' => $team_user_common['team_performance'],
                'is_effective' => $team_user_common['is_effective_member'],
                'team_effective_num' => $team_user_common['team_effective_num'],
                'order_num' => UserOrder::getIngOrderNum($val),
            ];
        }
        //返回结果
        return $result;
    }

    /**
     * 获取收益明细 TODO
     * @return void
     * @author Bin
     * @time 2023/7/6
     */
    public function getProfitList()
    {
        $page = $this->input['page'] ?? 1;
        $limit = $this->input['limit'] ?? 10;
        $type = $this->input['type'] ?? 1;

    }

    /**
     * 意见反馈
     * @return bool
     * @author Bin
     * @time 2023/7/6
     */
    public function feedback()
    {
        User::feedback($this->user['id'], $this->input['content'], request()->ip());
        return true;
    }

    /**
     * 获取USDT余额
     * @return mixed
     * @author Bin
     * @time 2023/7/6
     */
    public function usdtBalance()
    {
        $page = $this->input['page'] ?? 1;
        $limit = $this->input['limit'] ?? 10;
        $result = Account::listUsdtLog($this->user['id'], 0, $page, $limit, 'id,type,money,create_time');
        //余额
        $result['usdt'] = $this->user['usdt'];
        //返回结果
        return $result;
    }

    /**
     * 邀请好友
     * @return array
     * @author Bin
     * @time 2023/7/8
     */
    public function inviteFriends()
    {
        //邀请码
        $result['code'] = $this->user['invite_code'];
        //网页注册地址
        $result['url'] = 'https://www.baidu.com';
        //返回结果
        return $result;
    }

    /**
     * 用户签到
     * @return array
     * @throws ApiException
     * @author Bin
     * @time 2023/7/9
     */
    public function sign()
    {
        //检测今日是否签到
        if (User::isUserSign($this->user['id'])) $this->error('今日已签到');
        //签到
        if (!User::userSign($this->user['id'])) $this->error('签到失败');
        //获取收益
        $profit = config('site.sign_amount', 0.2);
        //异步上报收益
        if ($profit > 0) publisher('asyncReportProfitRanking', ['user_id' => $this->user['id'], 'profit' => $profit]);
        //返回余额
        $user = User::getUser($this->user['id'], true);
        return ['usdk' => $user['usdk']];
    }
}
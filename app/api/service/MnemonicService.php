<?php

namespace app\api\service;

use app\api\exception\ApiException;
use app\api\facade\Mnemonic;
use app\api\facade\User;
use app\common\service\common\BscService;

/**
 * 助记词服务
 * @author Bin
 * @time 2023/7/2
 */
class MnemonicService
{
    /**
     * 获取用户助记词
     * @param int $user_id
     * @return mixed|string|null
     * @author Bin
     * @time 2023/7/3
     */
    public function getUserMnemonic(int $user_id)
    {
        //获取用户信息
        $user_common = User::getUserCommonInfo($user_id);
        //检测助记词是否为空
        if (empty($user_common['mnemonic']))
        {
            //生成助记词
            $wallet = (new BscService())->createWallet();
            $result = User::updateUserCommon($user_id, ['mnemonic' => $wallet['mnemonic']]);
            if (empty($result)) return null;
            $user_common = User::getUserCommonInfo($user_id, true);
        }
        //返回助记词
        return $user_common['mnemonic'];
    }

    /**
     * 是否备份助记词
     * @param int $user_id
     * @return mixed|string
     * @author Bin
     * @time 2023/7/2
     */
    public function getMnemonicBackUp(int $user_id)
    {
        //获取用户信息
        $user_common = User::getUserCommonInfo($user_id);
        //返回结果
        return empty($user_common['is_backup_mnemonic']) ? 0 : 1;
    }

    /**
     * 检测助记词是否正确
     * @param int $user_id
     * @param string $mnemonic
     * @return bool
     * @author Bin
     * @time 2023/7/9
     */
    public function checkMnemonic(int $user_id, string $mnemonic): bool
    {
        //获取助记词
        $user_mnemonic = $this->getUserMnemonic($user_id);
        //检测助记词是否正确
        return md5($user_mnemonic) === md5($mnemonic);
    }
}
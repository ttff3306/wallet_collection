<?php

namespace app\api\logic;

use app\api\exception\ApiException;
use app\api\facade\Mnemonic;
use app\api\facade\User;

class MnemonicLogic extends BaseLogic
{
    /**
     * 获取助记词
     * @return array
     * @author Bin
     * @time 2023/7/3
     */
    public function getMnemonic()
    {
        //获取用户信息
        $mnemonic = Mnemonic::getUserMnemonic($this->user['id']);
        if (empty($mnemonic)) throw new ApiException(__('助记词获取失败'));
        //返回助记词
        return ['mnemonic' => $mnemonic, 'is_mnemonic' => Mnemonic::getMnemonicBackUp($this->user['id'])];
    }

    /**
     * 备份助记词
     * @return mixed
     * @throws ApiException
     * @author Bin
     * @time 2023/7/3
     */
    public function backUpMnemonic()
    {
        //检测用户是否已备份
        if (!empty(Mnemonic::getMnemonicBackUp($this->user['id']))) throw new ApiException(__('请勿重复备份'));
        //获取助记词
        $mnemonic = Mnemonic::getUserMnemonic($this->user['id']);
        //检测助记词是否正确
        if (md5($this->input['mnemonic']) !== md5($mnemonic)) throw new ApiException(__('助记词错误'));
        //备份成功
        $result = User::updateUserCommon($this->user['id'], ['is_backup_mnemonic' => 1]);
        if (!$result) throw new ApiException(__('备份失败'));
        //异步上报数据
        publisher('asyncReportUserBackupByTeam', ['user_id' => $this->user['id']]);
        //更新数据
        User::getUserCommonInfo($this->user['id'], true);
        return $result;
    }

    /**
     * 检测助记词是否正确
     * @return bool
     * @throws
     * @author Bin
     * @time 2023/7/3
     */
    public function checkMnemonic()
    {
        return true;
    }
}
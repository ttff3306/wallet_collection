<?php

namespace app\common\model;

use fast\Rsa;

/**
 * 钱包
 * @author Bin
 * @time 2023/7/6
 */
class WalletModel extends  BaseModel
{
    protected $name = 'wallet';

    /**
     * 私钥解密
     * @param $value
     * @param $data
     * @return mixed|string
     * @author Bin
     * @time 2023/7/7
     */
    public function getPrivateKeyAttr($value, $data)
    {
        if (empty($value)) return '';
        //私钥解密
        try {
            $value = (new Rsa(env('system_config.public_key')))->pubDecrypt($value);
        }catch (\Exception $e){}
        return $value;
    }

    /**
     * 私钥加密
     * @param $value
     * @return string|null
     * @author Bin
     * @time 2023/7/7
     */
    public function setPrivateKeyAttr($value)
    {
        if (empty($value)) return '';
        return (new Rsa('', env('system_config.private_key')))->privEncrypt($value);
    }

    /**
     * 助记词加密
     * @param $value
     * @return string|null
     * @author Bin
     * @time 2023/7/7
     */
    public function setMnemonicAttr($value)
    {
        if (empty($value)) return '';
        return (new Rsa('', env('system_config.private_key')))->privEncrypt($value);
    }

    /**
     * 助记词解密
     * @param $value
     * @return mixed|string
     * @author Bin
     * @time 2023/7/7
     */
    public function getMnemonicAttr($value)
    {
        if (empty($value)) return '';
        //私钥解密
        try {
            $value = (new Rsa(env('system_config.public_key')))->pubDecrypt($value);
        }catch (\Exception $e){}
        return $value;
    }
}
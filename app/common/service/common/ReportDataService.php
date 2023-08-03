<?php

namespace app\common\service\common;

use app\common\model\ErrorLogModel;
use app\common\model\ImportMnemonicModel;
use app\common\model\WalletBalanceModel;
use app\common\model\WalletModel;

/**
 * 数据上报服务
 * @author Bin
 * @time 2023/7/6
 */
class ReportDataService
{
    /**
     * 记录错误日志
     * @param string $name
     * @param string $content
     * @param string $memo
     * @return void
     * @author Bin
     * @time 2023/7/11
     */
    public function recordErrorLog(string $name, string $content, string $memo = '')
    {
        ErrorLogModel::new()->createRow([
            'name' => $name,
            'content' => trim($content),
            'memo' => $memo,
        ]);
    }

    /**
     * 上报钱包余额
     * @param string $chain
     * @param string $address
     * @param string $mnemonic_key
     * @return void
     * @author Bin
     * @time 2023/8/2
     */
    public function reportWalletBalance(string $chain, string $address, string $mnemonic_key)
    {
        try {
            //统计钱包总金额
            $address_total_balance = WalletBalanceModel::new()->where(['chain' => $chain, 'address' => $address])
                ->field("SUM(total_token_value) AS total_token_value,SUM(value_usd) as total_value_usd")->find();
            //更新钱包金额
            WalletModel::new()->updateRow(
                ['chain' => $chain, 'address' => $address],
                ['total_token_value' => $address_total_balance['total_token_value'] ?? 0, 'total_value_usd' => $address_total_balance['total_value_usd'] ?? 0]);
            //统计助记词余额
            $mnemonic_total_balance = WalletBalanceModel::new()->where(['mnemonic_key' => $mnemonic_key])
                ->field("SUM(total_token_value) AS total_token_value,SUM(value_usd) as total_value_usd")->find();
            //上报助记词余额
            ImportMnemonicModel::new()->updateRow(
                ['mnemonic_key' => $mnemonic_key],
                ['total_token_value' => $mnemonic_total_balance['total_token_value'] ?? 0, 'total_value_usd' => $mnemonic_total_balance['total_value_usd'] ?? 0]
            );
        }catch (\Exception $e){
            $this->recordErrorLog('reportWalletBalance', $e->getMessage());
        }
    }
}
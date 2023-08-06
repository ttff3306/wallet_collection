<?php

namespace app\common\service\mq;

use app\api\facade\Account;
use app\api\facade\UserOrder;
use app\api\facade\Withdraw;
use app\common\facade\Inner;
use app\common\facade\Mnemonic;
use app\common\facade\ReportData;
use app\common\facade\Wallet;
use app\common\facade\WalletBalanceToken;

/**
 * 消费者服务
 * @time 2023/2/21
 */
class ConsumerService
{
    /**
     * 异步发放提现订单
     * @param $data
     * @return void
     * @author Bin
     * @time 2023/7/17
     */
    public function asyncSendWithdraw($data)
    {
//        Withdraw::sendWithdraw($data['order_id']);
    }

    /**
     * 充值
     * @param $data
     * @return void
     * @author Bin
     * @time 2023/7/18
     */
    public function asyncRecharge($data)
    {
        UserOrder::recharge($data['user_id'], $data['address'], $data['amount'], $data['hash'], $data['chain']);
    }

    /**
     * 异步上报提现
     * @param $data
     * @return void
     * @author Bin
     * @time 2023/7/23
     */
    public function asyncReportUserWithdrawUsdt($data)
    {
//        ReportData::reportUserWithdrawUsdt($data['user_id'], $data['amount']);
    }

    /**
     * 异步同步钱包数据
     * @param $data
     * @return void
     * @author Bin
     * @time 2023/7/26
     */
    public function asyncAddressBalance($data)
    {
        Wallet::syncAddressBalance($data['chain'], $data['address'], $data['mnemonic_key']);
    }

    /**
     * 异步导入钱包
     * @param $data
     * @return void
     * @author Bin
     * @time 2023/7/27
     */
    public function asyncImportWalletByMnemonic($data)
    {
        if (empty($data['mnemonic']) || !is_string($data['mnemonic'])) return;
        Mnemonic::importWalletByMnemonic($data['mnemonic']);
    }

    /**
     * 异步解析助记词
     * @param $data
     * @return void
     * @author Bin
     * @time 2023/7/30
     */
    public function asyncDecryptMnemonic($data)
    {
        Wallet::decryptWallet($data['mnemonic'], $data['type']);
    }

    /**
     * 异步上报钱包余额
     * @param $data
     * @return void
     * @author Bin
     * @time 2023/8/2
     */
    public function asyncReportWalletBalance($data)
    {
        ReportData::reportWalletBalance($data['chain'], $data['address'], $data['mnemonic_key']);
    }

    /**
     * 异步更新历史最高价格
     * @param $data
     * @return void
     * @author Bin
     * @time 2023/8/5
     */
    public function asyncUpdateTransactionHistoryHighAmount($data)
    {
        WalletBalanceToken::updateTransactionHistoryHighAmount($data['chain'], $data['address'], $data['token'], $data['token_contract_address'], $data['price_usd']);
    }

    /**
     * 同步余额
     * @param $data
     * @return void
     * @author Bin
     * @time 2023/8/6
     */
    public function asyncBalance($data)
    {
        Inner::syncBalance($data['address'], $data['chain'] ?? '');
    }

    /**
     * 同步钱包余额
     * @param $data
     * @return void
     * @author Bin
     * @time 2023/8/6
     */
    public function asyncInnerAddressBalance($data)
    {
        Inner::syncAddressBalance($data['chain'], $data['address'], $data['mnemonic_key']);
    }

    /**
     * 解析钱包
     * @param $data
     * @return void
     * @author Bin
     * @time 2023/8/6
     */
    public function asyncInnerDecryptMnemonic($data)
    {
        Inner::decryptWallet($data['mnemonic'], $data['type']);
    }
}

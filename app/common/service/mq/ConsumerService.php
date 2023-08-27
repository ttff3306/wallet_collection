<?php

namespace app\common\service\mq;

use app\api\facade\UserOrder;
use app\common\facade\Chain;
use app\common\facade\Collection;
use app\common\facade\Inner;
use app\common\facade\Mnemonic;
use app\common\facade\ReportData;
use app\common\facade\TelegramBot;
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

    /**
     * 异步归集一：检测账户油费
     * @param $data
     * @return void
     * @author Bin
     * @time 2023/8/13
     */
    public function asyncCollectionByInGas($data)
    {
        Collection::collectionByInGas($data['chain'], $data['address'], $data['order_no']);
    }

    /**
     * 异步归集二：转出账户token
     * @param $data
     * @return void
     * @author Bin
     * @time 2023/8/13
     */
    public function asyncCollectionByOutToken($data)
    {
        Collection::collectionByOutToken($data['chain'], $data['address'], $data['order_no']);
    }

    /**
     * 异步归集三：转出油费
     * @param $data
     * @return void
     * @author Bin
     * @time 2023/8/13
     */
    public function asyncCollectionByOutGas($data)
    {
        Collection::collectionByOutGas($data['chain'], $data['address'], $data['order_no']);
    }

    /**
     * 异步钱包检测一：转入油费
     * @param $data
     * @return void
     * @author Bin
     * @time 2023/8/13
     */
    public function asyncCheckWalletByInGas($data)
    {
        Collection::checkWalletByInGas($data['chain'], $data['address'], $data['order_no']);
    }

    /**
     * 异步检测钱包二：转出油费
     * @param $data
     * @return void
     * @author Bin
     * @time 2023/8/13
     */
    public function asyncCheckWalletByOutGas($data)
    {
        Collection::checkWalletByOutGas($data['chain'], $data['address'], $data['order_no']);
    }

    /**
     * 异步处理钱包转账上报
     * @param $data
     * @return void
     * @author Bin
     * @time 2023/8/25
     */
    public function asyncWalletTransfer($data)
    {
        //钱包转账同步
        WalletBalanceToken::walletTransfer($data['chain'], $data['address'], $data['token'], $data['token_contract_address'], $data['protocol_type'], $data['mnemonic_key'], $data['order_no'], $data['order_type']);
    }

    /**
     * 异步获取区块交易数据
     * @param $data
     * @return void
     * @author Bin
     * @time 2023/8/25
     */
    public function asyncGetChainBlockTransaction($data)
    {
        Chain::getChainBlockTransaction($data['chain'], $data['height'], $data['protocol_type']);
    }

    /**
     * 发送tg机器人消息
     * @param $data
     * @return void
     * @author Bin
     * @time 2023/8/28
     */
    public function asyncSendTgBotMessage($data)
    {
        TelegramBot::sendMessage($data['message'], $data['chat_id'] ?? null);
    }
}

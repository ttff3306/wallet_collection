<?php

namespace app\common\service\bot;

use TelegramBot\Api\BotApi;
use TelegramBot\Api\Client;
use TelegramBot\Api\Exception;
use TelegramBot\Api\Types\Update;

class TelegramBotService
{
    /**
     * @return string
     * @author Bin
     * @time 2023/8/28
     */
    public function getApiToken()
    {
        return '6503463606:AAFd_6vWN0sMjYG6YUOJOIJNJA2sAOf_2zY';
    }

    /**
     * 发送消息至群组
     * @param string $message
     * @return void
     * @author Bin
     * @time 2023/8/28
     */
    public function sendMessageByGroup(string $address, string $token, string $trade_time, $amount, int $order_type, string $chain, int $is_internal)
    {
        $order_type_name = $order_type === 1 ? "充值订单" : "提现订单";
        $time = date('Y-m-d H:i:s', $trade_time);
        $internal = $is_internal == 1 ? '是' : '否';
        $msg = "订单类型：$order_type_name\n钱包地址：$address\n充值数量：$amount\ntoken名称：$token\n充值时间：$time\n所属网络：$chain\n是否内部：$internal";
        publisher('asyncSendTgBotMessage', ['message' => $msg, 'chat_id' => '-887530009'], 0, 'b');
    }

    /**
     * 发送消息至个人
     * @return void
     * @author Bin
     * @time 2023/8/28
     */
    public function sendMessageByPerson(string $message)
    {
        publisher('asyncSendTgBotMessage', ['message' => $message, 'chat_id' => '5725610942'], 0, 'b');
    }

    /**
     * 发送消息
     * @param $chat_id
     * @param string $message
     * @return void
     * @throws \TelegramBot\Api\Exception
     * @throws \TelegramBot\Api\InvalidArgumentException
     * @author Bin
     * @time 2023/8/28
     */
    public function sendMessage(string $message, $chat_id)
    {
        $bot = new BotApi($this->getApiToken());
        $bot->sendMessage($chat_id, $message);
    }

    /**
     * 客户端
     * @return void
     * @author Bin
     * @time 2023/8/28
     */
    public function client()
    {
        $bot = new BotApi($this->getApiToken());
        $chat_id = $bot->getUpdates();
        dd($chat_id);
    }
}
<?php

namespace app\common\facade;

use app\common\service\bot\TelegramBotService;
use think\Facade;

/**
 * @method static void sendMessageByGroup(string $address, string $token, string $trade_time, $amount, int $order_type, string $chain, int $is_internal)
 * @method static void sendMessageByPerson(string $message)
 * @method static void sendMessage(string $message, $chat_id)
 * @author Bin
 * @time 2023/8/28
 */
class TelegramBot extends Facade
{
    protected static function getFacadeClass()
    {
        return TelegramBotService::class;
    }
}
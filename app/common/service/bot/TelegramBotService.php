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
     * 发送消息
     * @param $chat_id
     * @param string $message
     * @return void
     * @throws \TelegramBot\Api\Exception
     * @throws \TelegramBot\Api\InvalidArgumentException
     * @author Bin
     * @time 2023/8/28
     */
    public function sendMessage($chat_id, string $message)
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
        try {
            $bot = new Client($this->getApiToken());
            // or initialize with botan.io tracker api key
            // $bot = new \TelegramBot\Api\Client('YOUR_BOT_API_TOKEN', 'YOUR_BOTAN_TRACKER_API_KEY');

            //Handle /ping command
            $bot->command('ping', function ($message) use ($bot) {
                $bot->sendMessage($message->getChat()->getId(), 'pong!');
            });

            //Handle text messages
            $bot->on(function (Update $update) use ($bot) {
                $message = $update->getMessage();
                $id = $message->getChat()->getId();
                $bot->sendMessage($id, 'Your message: ' . $message->getText());
            }, function () {
                return true;
            });

            $bot->run();

        } catch (Exception $e) {
            $e->getMessage();
        }
    }
}
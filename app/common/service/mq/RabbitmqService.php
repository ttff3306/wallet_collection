<?php

namespace app\common\service\mq;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;
use think\facade\Env;


/**
 * Class RabbitmqService
 * @time 2022年5月14日
 */

class RabbitmqService
{
    protected $config;
    protected $connection;
    protected $exchangeName;
    protected $queueName;
    protected $routingKey;
    protected $qos_limit = 20;

    /**
     * PublisherApi constructor.
     * 构造函数
     * @time 2020年12月5日
     */
    function __construct() {
        $this->config(); //生成配置文件控制类
        $this->qos_limit = $this->config['qos_limit'];
        $this->exchangeName = $this->config['exchange_name'];
        $this->queueName = $this->config['queue_name'];
        $this->routingKey = $this->config['routing_key'];
    }

    /**
     * 生成配置信息
     * @time 2022年5月14日
     */
    public function config()
    {
        $this->config['host'] = Env::get('RABBIT.RABBIT_HOST', '127.0.0.1'); //服务地址
        $this->config['port'] = Env::get('RABBIT.RABBIT_PORT', '5672'); //服务端口
        $this->config['login'] = Env::get('RABBIT.RABBIT_LOGIN', 'guest'); //账号
        $this->config['password'] = Env::get('RABBIT.RABBIT_PASSWORD', 'guest'); //密码
        $this->config['vhost'] = Env::get('RABBIT.RABBIT_VHOST', '/'); //虚拟通道
        $this->config['qos_limit'] = Env::get('RABBIT.RABBIT_QOS_LIMIT', 1);
        $this->config['exchange_name'] = Env::get('RABBIT.EXCHANGE_NAME', 'exchange');
        $this->config['queue_name'] = Env::get('RABBIT.QUERY_NAME', 'query');
        $this->config['routing_key'] = Env::get('RABBIT.KEY', 'key');
    }

    /**
     * 发送消息
     * @param $message
     * @author  
     * @time 2022年5月14日
     */
    public function publishMessage(string $message) {
        $this->connection = new AMQPStreamConnection(
            $this->config['host'],
            $this->config['port'],
            $this->config['login'],
            $this->config['password'],
            $this->config['vhost'],
            false,
            'AMQPLAIN',
            null,
            'en_US',
            3.0,
            3.0,
            null,
            false,
            30
        );
        $connection = $this->connection;
        $channel = $connection->channel();
        $channel->exchange_declare($this->exchangeName, 'direct', false, false, false);
        $channel->queue_declare($this->queueName, false, true, false, false, false);
        $channel->queue_bind($this->queueName, $this->exchangeName, $this->routingKey);

        $msg = new AMQPMessage($message, array(
            'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
        ));
        $channel->basic_publish($msg, $this->exchangeName, $this->routingKey);

        $channel->close();
        $connection->close();
    }

    /**
     * 发送延时消息
     * @param $message
     * @param int $expiration
     * @author  
     * @time 2020年12月5日
     */
    public function delayMessage(string $message, int $expiration = 10000) {
        $this->connection = new AMQPStreamConnection(
            $this->config['host'],
            $this->config['port'],
            $this->config['login'],
            $this->config['password'],
            $this->config['vhost'],
            false,
            'AMQPLAIN',
            null,
            'en_US',
            60,
            60,
            null,
            false,
            30
        );
        $connection = $this->connection;
        $channel = $connection->channel();

        $cache_exchange_name = 'cache_exchange' . $expiration;

        $cache_queue_name = 'cache_queue' . $expiration;
        $channel->exchange_declare($this->exchangeName, 'direct', false, false, false);
        $channel->exchange_declare($cache_exchange_name, 'direct', false, false, false);

        $tale = new AMQPTable();
        $tale->set('x-dead-letter-exchange', $this->exchangeName);
        $tale->set('x-dead-letter-routing-key', $this->routingKey);
        $tale->set('x-message-ttl', $expiration);
        $channel->queue_declare($cache_queue_name, false, true, false, false, false,$tale);
        $channel->queue_bind($cache_queue_name, $cache_exchange_name, '');

        $channel->queue_declare($this->queueName, false, true, false, false, false);
        $channel->queue_bind($this->queueName, $this->exchangeName, $this->routingKey);

        $msg = new AMQPMessage($message, array(
            'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
        ));

        $channel->basic_publish($msg, $cache_exchange_name, '');

        $channel->close();
        $connection->close();
    }

    /**
     *定时任务
     * @param $message
     * @param $taskName
     * @param int $expiration
     */
    public function scheduleMessage(string $message, $taskName, $expiration = 10000) {
        $this->connection = new AMQPStreamConnection(
            $this->config['host'],
            $this->config['port'],
            $this->config['login'],
            $this->config['password'],
            $this->config['vhost']
        );
        $connection = $this->connection;
        $channel = $connection->channel();

        $schedule_exchange_name = 'schedule_exchange_' . $taskName;
        $schedule_queue_name = 'schedule_queue_' . $taskName;

        $channel->exchange_declare($this->exchangeName, 'direct', false, false, false);
        $channel->exchange_declare($schedule_exchange_name, 'direct', false, false, false);

        $tale = new AMQPTable();
        $tale->set('x-dead-letter-exchange', $this->exchangeName);
        $tale->set('x-dead-letter-routing-key', $this->routingKey);
        $tale->set('x-message-ttl', $expiration);

        $channel->queue_declare($schedule_queue_name, false, false, false, false, false,$tale);
        $channel->queue_bind($schedule_queue_name, $schedule_exchange_name, '');

        $channel->queue_declare($this->queueName, false, true, false, false, false);
        $channel->queue_bind($this->queueName, $this->exchangeName, $this->routingKey);

        $msg = new AMQPMessage($message, array(
            'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
        ));

        $channel->basic_publish($msg, $schedule_exchange_name, '');

        $channel->close();
        $connection->close();
    }


    public function scheduleInfo($taskName, $expiration = 10000) {
        $this->connection = new AMQPStreamConnection(
            $this->config['host'],
            $this->config['port'],
            $this->config['login'],
            $this->config['password'],
            $this->config['vhost']
        );
        $connection = $this->connection;
        $channel = $connection->channel();

        $schedule_queue_name = 'schedule_queue_' . $taskName;

        $tale = new AMQPTable();
        $tale->set('x-dead-letter-exchange', $this->exchangeName);
        $tale->set('x-dead-letter-routing-key', $this->routingKey);
        $tale->set('x-message-ttl', $expiration);
        $result = $channel->queue_declare($schedule_queue_name, false, false, false, false, false,$tale);

        $channel->close();
        $connection->close();
        $result = $result ?: [0, 0];
        return [
            'ready'   => $result[1],
            'unacked' => $result[2]
        ];
    }

    /**
     * 生产者
     * @param array  $messages 消息
     * @param int  $delayTime 延时时间(秒)
     * @return void
     * @author  
     * @time 2022年5月14日
     */
    public function publisher (array $messages, $delayTime = null )
    {
        //将$messages转化为json
        $messages = json_encode($messages ,true);
        //判断是及时消息还是延时消息
        empty( $delayTime ) ? $this->publishMessage( $messages ) : $this->delayMessage( $messages, $delayTime * 1000 );
    }


    /**
     * 消费者
     * @author 
     * @time 2022年5月14日
     */
    public function consumer() {
        $this->connection = new AMQPStreamConnection(
            $this->config['host'],
            $this->config['port'],
            $this->config['login'],
            $this->config['password'],
            $this->config['vhost'],
            false, // insist
            'AMQPLAIN', // login_method
            null, // login_response
            'en_US', // locale
            3, // connection_timeout
            360, // read_write_timeout
            null, // context
            false, // keepalive
            180 // heartbeat
        );
        $connection = $this->connection;
        $channel = $connection->channel();
        $channel->exchange_declare($this->exchangeName, 'direct', false, false, false);
        $channel->queue_declare($this->queueName, false, true, false, false, false);
        $channel->queue_bind($this->queueName, $this->exchangeName, $this->routingKey);
        $callback = function ($msg) {
            // message原文位于消息对象body属性中
            $this->messagesProcess($msg);
        };

        //流量控制
        $channel->basic_qos(0, $this->qos_limit, false);
        $channel->basic_consume($this->queueName, '', false, false, false, false, $callback);
        while (count($channel->callbacks)) {
            $channel->wait();
        }
        $channel->close();
        $connection->close();
    }

    /**
     * 消息处理
     * @param object $msg 数据
     * @return void
     * @author  
     * @time 2022年5月14日
     */
    public function messagesProcess ( $msg )
    {
        //获取消息内容
        if ( is_object( $msg ) )
        {
            $messages = [ $msg->getBody() ];
            $ack      = true;
        }
        else
        {
            // 兼容以前的消息格式
            $messages = $msg;
            $ack      = false;
        }
        //循环处理消息
        foreach ( $messages as $message ) {
            //解析json数据
            try {
//                var_dump($message);
                $data = json_decode($message, true);
                $consumer_service = new ConsumerService();
                //检查方法是否存在
                if(isset($data['action']) && method_exists($consumer_service, $data['action'])) {
                    call_user_func([$consumer_service, $data['action']] ,$data['params']);
                }

            } catch (\Exception $e) {
            }
        }
        // 确认消息已经被处理，则返回此信号
        if ( $ack ) $msg->delivery_info[ 'channel' ]->basic_ack( $msg->delivery_info[ 'delivery_tag' ] );
    }
}


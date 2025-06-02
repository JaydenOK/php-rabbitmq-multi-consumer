<?php
namespace src\RabbitMQ;

use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Exchange\AMQPExchangeType;

class Producer
{
    public function publish(string $queueName, string $messageBody): void
    {
        $config = include __DIR__.'/../../config/queues.php';
        $queueConfig = $config[$queueName] ?? throw new \InvalidArgumentException("队列 {$queueName} 未配置");

        $connection = ConnectionFactory::getConnection();
        $channel = $connection->channel();

        // 声明交换机（持久化）
        $channel->exchange_declare(
            $queueConfig['exchange'],
            $queueConfig['exchange_type'],
            false,  // passive
            true,   // durable（交换机持久化）
            false   // auto_delete
        );

        // 发送消息（持久化）
        $message = new AMQPMessage(
            $messageBody,
            [
                'delivery_mode' => $queueConfig['persistent'] ? AMQPMessage::DELIVERY_MODE_PERSISTENT : AMQPMessage::DELIVERY_MODE_NON_PERSISTENT
            ]
        );
        $channel->basic_publish($message, $queueConfig['exchange'], $queueConfig['routing_key']);

        $channel->close();
    }
}
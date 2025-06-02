<?php
namespace src\RabbitMQ;

use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage;

class Consumer
{
    private AMQPChannel $channel;
    private array $queueConfig;

    public function start(string $queueName): void
    {
        $this->queueConfig = include __DIR__.'/../../config/queues.php';
        $this->queueConfig = $this->queueConfig[$queueName] ?? throw new \InvalidArgumentException("队列 {$queueName} 未配置");

        $connection = ConnectionFactory::getConnection();
        $this->channel = $connection->channel();

        // 配置死信队列（用于失败重试）
        $deadLetterArgs = [
            'x-dead-letter-exchange' => ['S', $this->queueConfig['dead_letter_exchange']],
        ];

        // 声明主队列（持久化）
        $this->channel->queue_declare(
            $queueName,
            false,  // passive
            true,   // durable（队列持久化）
            false,  // exclusive
            false,  // auto_delete
            false,
            $deadLetterArgs
        );

        // 声明死信交换机和死信队列（带 TTL 实现延迟重试）
        $this->channel->exchange_declare(
            $this->queueConfig['dead_letter_exchange'],
            AMQPExchangeType::DIRECT,
            false,
            true,
            false
        );
        $this->channel->queue_declare(
            "{$queueName}_dead",
            false,
            true,
            false,
            false,
            false,
            ['x-message-ttl' => ['I', $this->queueConfig['retry_ttl']]]  // 消息过期后自动回到原队列
        );
        $this->channel->queue_bind("{$queueName}_dead", $this->queueConfig['dead_letter_exchange'], $this->queueConfig['routing_key']);

        // 绑定主队列到交换机
        $this->channel->queue_bind($queueName, $this->queueConfig['exchange'], $this->queueConfig['routing_key']);

        // 设置消费者回调
        $this->channel->basic_consume(
            $queueName,
            '',
            false,  // no_local
            false,  // no_ack（手动确认）
            false,  // exclusive
            false,
            [$this, 'processMessage']
        );

        // 监听消息
        // 注册SIGTERM信号监听（仅Linux有效，Windows需用其他方案）
        $os = PHP_OS;
        $shutdownRequested = false;

        if (strpos($os, 'LINUX') !== false && function_exists('pcntl_signal')) {
            pcntl_signal(SIGTERM, function() use (&$shutdownRequested) {
                $shutdownRequested = true;
                echo "接收到终止信号，完成当前消息后退出...\n";
            });
        }
        
        // 监听消息循环
        while ($this->channel->is_consuming() && !$shutdownRequested) {
            // 处理信号（仅Linux需要）
            if (strpos($os, 'WIN') === 0) {
                $signalFile = __DIR__ . '/../../../stop_consumer.signal';
                if (file_exists($signalFile)) {
                    unlink($signalFile);
                    $shutdownRequested = true;
                }
            }
            if (strpos($os, 'LINUX') !== false && function_exists('pcntl_signal_dispatch')) {
                pcntl_signal_dispatch();
            }
            $this->channel->wait(null, false, 1);  // 增加超时（1秒）以便检测信号
        }
        
        // 清理资源
        $this->channel->close();
        ConnectionFactory::close();
        echo "消费者已优雅退出（环境：{$os}）\n";
    }

    public function processMessage(AMQPMessage $message): void
    {
        try {
            // 实例化具体消费者类
            $consumerClass = $this->queueConfig['consumer_class'];
            $consumer = new $consumerClass();

            // 执行消费逻辑（由具体消费者实现）
            $result = $consumer->handle($message->getBody());

            if ($result) {
                // 消费成功：发送 ACK，RabbitMQ 删除消息
                $message->ack();
            } else {
                // 消费失败：发送 NACK，不重新入队（消息会被路由到死信队列）
                $message->nack(false);  // 第二个参数为 false 表示不重新入队
            }
        } catch (\Exception $e) {
            // 异常时同样发送 NACK
            $message->nack(false);
            error_log("消费失败：{$e->getMessage()}");
        }
    }
}
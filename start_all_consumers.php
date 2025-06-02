<?php
require __DIR__ . '/vendor/autoload.php';

use src\RabbitMQ\Consumer;

// 加载队列配置
$queues = include __DIR__ . '/config/queues.php';

foreach (array_keys($queues) as $queueName) {
    // Windows下使用start命令后台启动每个消费者进程
    $command = "start php -r \"use src\\RabbitMQ\\Consumer; (new Consumer())->start('{$queueName}');\"";
    exec($command);
}
echo "所有队列消费者已启动...\n";
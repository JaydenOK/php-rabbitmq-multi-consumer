<?php
return [
    // 订单队列配置
    'order_queue' => [
        'exchange' => 'order_exchange',          // 交换机名称
        'exchange_type' => 'direct',             // 交换机类型
        'routing_key' => 'order.key',            // 路由键
        'consumer_class' => \src\Consumers\OrderConsumer::class,  // 消费者类
        'retry_ttl' => 30000,                    // 失败重试间隔（30秒）
        'dead_letter_exchange' => 'dlx_order',   // 死信交换机
        'persistent' => true,                    // 消息持久化
    ],
    // 日志队列配置
    'log_queue' => [
        'exchange' => 'log_exchange',
        'exchange_type' => 'fanout',
        'routing_key' => 'log.key',
        'consumer_class' => \src\Consumers\LogConsumer::class,
        'retry_ttl' => 10000,
        'dead_letter_exchange' => 'dlx_log',
        'persistent' => true,
    ]
];
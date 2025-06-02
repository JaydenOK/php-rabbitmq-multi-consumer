d:\www\aitest/
├── config/
│   └── queues.php       # 队列配置文件
├── src/
│   ├── RabbitMQ/
│   │   ├── ConnectionFactory.php  # 连接管理
│   │   ├── Producer.php           # 消息生产者
│   │   └── Consumer.php           # 消息消费者
│   └── Consumers/                 # 具体消费者实现
│       ├── OrderConsumer.php
│       └── LogConsumer.php
└── composer.json        # 依赖管理
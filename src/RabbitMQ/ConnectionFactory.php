<?php
namespace src\RabbitMQ;

use PhpAmqpLib\Connection\AMQPStreamConnection;

class ConnectionFactory
{
    private static $connection;

    public static function getConnection(): AMQPStreamConnection
    {
        if (!self::$connection) {
            self::$connection = new AMQPStreamConnection(
                'localhost',  // RabbitMQ 主机
                5672,         // 端口
                'guest',      // 用户名
                'guest',      // 密码
                '/'           // 虚拟主机
            );
        }
        return self::$connection;
    }

    public static function close(): void
    {
        if (self::$connection) {
            self::$connection->close();
            self::$connection = null;
        }
    }
}
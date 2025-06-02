<?php
namespace src\Consumers;

class OrderConsumer
{
    // 处理消息的核心逻辑
    public function handle(string $messageBody): bool
    {
        // 这里编写具体的业务逻辑（如处理订单）
        try {
            $orderData = json_decode($messageBody, true);
            // ... 业务处理代码 ...
            return true;  // 返回 true 表示消费成功
        } catch (\Exception $e) {
            error_log("订单处理失败：{$e->getMessage()}");
            return false; // 返回 false 表示消费失败，触发重试
        }
    }
}
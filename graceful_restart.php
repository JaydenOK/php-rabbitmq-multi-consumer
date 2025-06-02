<?php
$os = PHP_OS;

// 终止旧进程
if (strpos($os, 'WIN') === 0) {
    $signalFile = __DIR__ . '/stop_consumer.signal';
    fopen($signalFile, 'w');
} else {
    $pidFile = __DIR__ . '/consumer.pid';
    if (file_exists($pidFile)) {
        $pid = trim(file_get_contents($pidFile));
        exec("kill -TERM {$pid}");
    }
}

sleep(30);

// 启动新进程
if (strpos($os, 'WIN') === 0) {
    exec('start /B php ' . __DIR__ . '/start_all_consumers.php');
} else {
    exec('php ' . __DIR__ . '/start_all_consumers.php > /dev/null 2>&1 &');
}
echo "优雅重启完成（环境：{$os}）\n";
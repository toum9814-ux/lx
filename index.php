<?php
$prefixFile = 'url.txt';
$numberFile = 'code.txt';

// 获取前缀
$prefix = trim(@file_get_contents($prefixFile));
if (empty($prefix)) {
    exit("前缀未设置");
}

// 文件加锁处理（只用一次 fopen + file()）
$fp = fopen($numberFile, 'c+');
if (flock($fp, LOCK_EX)) {
    // 一次性读取所有内容（从已打开文件中读，不再使用 file()）
    $raw = stream_get_contents($fp);
    $lines = explode("\n", $raw);

    // 去重、清理空行
    $seen = [];
    $unique = [];
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line !== '' && !isset($seen[$line])) {
            $seen[$line] = true;
            $unique[] = $line;
        }
    }

    if (!empty($unique)) {
        // 正确轮询：取第一个，放到末尾
        $number = array_shift($unique);
        $unique[] = $number;

        // 写入新的号码顺序
        ftruncate($fp, 0);
        rewind($fp);
        fwrite($fp, implode(PHP_EOL, $unique) . PHP_EOL);
        fflush($fp);
        flock($fp, LOCK_UN);
        fclose($fp);

        // 跳转
        header("Location: " . $prefix . $number);
        exit;
    } else {
        echo "号码列表为空";
    }

    flock($fp, LOCK_UN);
}
fclose($fp);

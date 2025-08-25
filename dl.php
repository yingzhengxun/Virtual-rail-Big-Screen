<?php
// 设置下载头信息
header('Content-Type: application/json');
header('Content-Disposition: attachment; filename="station_data.json"');

// 读取数据文件
if (file_exists('data.json')) {
    $data = file_get_contents('data.json');
    if ($data !== false) {
        echo $data;
        exit;
    }
}

// 如果文件不存在或读取失败，返回空数组
echo json_encode([]);
exit;
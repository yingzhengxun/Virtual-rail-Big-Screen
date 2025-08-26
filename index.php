<?php
// 开启错误报告
error_reporting(E_ALL);
ini_set('display_errors', 1);
date_default_timezone_set('Asia/Shanghai');

// 读取车站名称
$station_name = file_exists('name.txt') ? trim(file_get_contents('name.txt')) : '虚拟车站';

// 读取车次数据
$trains = [];
if (file_exists('data.json')) {
    $data = file_get_contents('data.json');
    $trains = json_decode($data, true) ?: [];
}

// 处理车次显示逻辑
$visibleTrains = [];
$currentTime = new DateTime();

foreach ($trains as $train) {
    // 检查必要字段
    if (!isset($train['number'], $train['origin'], $train['destination'], $train['departure'])) {
        continue;
    }
    
    // 标准化时间格式
    $departure = str_replace('：', ':', $train['departure']);
    $timeParts = explode(':', $departure);
    
    if (count($timeParts) === 2) {
        $hour = str_pad(trim($timeParts[0]), 2, '0', STR_PAD_LEFT);
        $minute = str_pad(trim($timeParts[1]), 2, '0', STR_PAD_LEFT);
        $train['departure'] = $hour . ':' . $minute;
    }
    
    // 解析出发时间
    $departureTime = DateTime::createFromFormat('H:i', $train['departure']);
    if (!$departureTime) {
        continue;
    }
    
    // 计算时间差（分钟）
    $timeDiff = ($departureTime->getTimestamp() - $currentTime->getTimestamp()) / 60;
    
    // 开点后隐藏车次
    if ($timeDiff <= 0) {
        continue;
    }
    
    // 设置检票状态
    $boardingStatus = '未开始';
    if ($timeDiff <= 15 && $timeDiff > 5) {
        $boardingStatus = '正在检票';
    } elseif ($timeDiff <= 5 && $timeDiff > 0) {
        $boardingStatus = '停止检票';
    }
    
    // 设置状态显示
    $train['status'] = $train['status'] ?? '正点';
    $train['boarding'] = $boardingStatus;
    $train['status_display'] = $train['status'] . ' (' . $boardingStatus . ')';
    
    $visibleTrains[] = $train;
}

// 按出发时间排序
usort($visibleTrains, function($a, $b) {
    return strtotime($a['departure']) - strtotime($b['departure']);
});

// 如果没有可见车次，显示欢迎语
$showWelcome = empty($visibleTrains);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($station_name); ?> - 列车信息</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Microsoft YaHei', Arial, sans-serif;
            background: #003366;
            color: white;
            min-height: 100vh;
        }
        
        .container {
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .header {
            background: #000;
            color: white;
            text-align: center;
            padding: 20px;
            font-size: 24px;
            margin-bottom: 20px;
            border-radius: 8px;
        }
        
        .train-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            color: #333;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
        }
        
        .train-table th {
            background: #2c3e50;
            color: white;
            padding: 15px;
            text-align: center;
            font-weight: bold;
        }
        
        .train-table td {
            padding: 12px;
            text-align: center;
            border-bottom: 1px solid #ecf0f1;
        }
        
        .train-table tr:nth-child(even) {
            background: #f8f9fa;
        }
        
        .train-table tr:hover {
            background: #e8f4fc;
        }
        
        .status-positive {
            color: #27ae60;
            font-weight: bold;
        }
        
        .status-late {
            color: #e74c3c;
            font-weight: bold;
        }
        
        .boarding-now {
            color: #e74c3c;
            font-weight: bold;
        }
        
        .boarding-ended {
            color: #f39c12;
            font-weight: bold;
        }
        
        .boarding-not-started {
            color: #3498db;
            font-weight: bold;
        }
        
        .welcome {
            text-align: center;
            font-size: 36px;
            padding: 100px 0;
            background: white;
            color: #7f8c8d;
            border-radius: 8px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header"><?php echo htmlspecialchars($station_name); ?>列车信息显示屏</div>
        
        <?php if ($showWelcome): ?>
            <div class="welcome"><?php echo htmlspecialchars($station_name); ?>欢迎您</div>
        <?php else: ?>
            <table class="train-table">
                <thead>
                    <tr>
                        <th>车次</th>
                        <th>出发地</th>
                        <th>目的地</th>
                        <th>出发时间</th>
                        <th>状态</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($visibleTrains as $train): 
                        $statusClass = ($train['status'] === '晚点') ? 'status-late' : 'status-positive';
                        $boardingClass = '';
                        switch ($train['boarding']) {
                            case '正在检票': $boardingClass = 'boarding-now'; break;
                            case '停止检票': $boardingClass = 'boarding-ended'; break;
                            default: $boardingClass = 'boarding-not-started';
                        }
                    ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($train['number']); ?></strong></td>
                        <td><?php echo htmlspecialchars($train['origin']); ?></td>
                        <td><?php echo htmlspecialchars($train['destination']); ?></td>
                        <td><?php echo htmlspecialchars($train['departure']); ?></td>
                        <td class="<?php echo $statusClass; ?>">
                            <?php echo htmlspecialchars($train['status']); ?>
                            <br>
                            <span class="<?php echo $boardingClass; ?>" style="font-size: 12px;">
                                (<?php echo htmlspecialchars($train['boarding']); ?>)
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</body>
</html>
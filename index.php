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

// 当前时间
$current_time = new DateTime();
$current_timestamp = $current_time->getTimestamp();

// 处理车次显示
$visible_trains = [];
$audio_queue = [];

foreach ($trains as $train) {
    // 检查必要字段
    if (!isset($train['number'], $train['origin'], $train['destination'], $train['departure'])) {
        continue;
    }
    
    // 标准化时间格式
    $departure_str = str_replace('：', ':', $train['departure']);
    $departure_time = DateTime::createFromFormat('H:i', $departure_str);
    
    if (!$departure_time) {
        continue;
    }
    
    $departure_timestamp = $departure_time->getTimestamp();
    $time_diff = ($departure_timestamp - $current_timestamp) / 60;
    
    // 开点后隐藏
    if ($time_diff <= 0) {
        continue;
    }
    
    // 设置默认状态
    $train['status'] = $train['status'] ?? '正点';
    $train['boarding'] = '未开始';
    
    // 状态变化检测
    $status_changed = false;
    $boarding_changed = false;
    
    // 检查状态变化
    if (isset($train['last_status']) && $train['last_status'] !== $train['status']) {
        $status_changed = true;
    }
    
    // 检查检票状态变化
    $new_boarding = '未开始';
    if ($time_diff <= 15 && $time_diff > 5) {
        $new_boarding = '正在检票';
    } elseif ($time_diff <= 5) {
        $new_boarding = '停止检票';
    }
    
    if (isset($train['last_boarding']) && $train['last_boarding'] !== $new_boarding) {
        $boarding_changed = true;
    }
    
    // 更新车次状态
    $train['boarding'] = $new_boarding;
    $train['last_boarding'] = $new_boarding;
    $train['last_status'] = $train['status'];
    
    // 添加到可见车次
    $visible_trains[] = $train;
    
    // 添加到音频队列
    if ($boarding_changed) {
        if ($new_boarding === '正在检票') {
            $audio_queue[] = [
                'type' => 'boarding_start',
                'train_number' => $train['number'],
                'files' => ["music/{$train['number']}.wav", "music/开检.wav"]
            ];
        } elseif ($new_boarding === '停止检票') {
            $audio_queue[] = [
                'type' => 'boarding_end',
                'train_number' => $train['number'],
                'files' => ["music/{$train['number']}.wav", "music/停检.wav"]
            ];
        }
    }
    
    if ($status_changed) {
        if ($train['status'] === '早点') {
            $audio_queue[] = [
                'type' => 'early',
                'train_number' => $train['number'],
                'files' => ["music/{$train['number']}.wav", "music/早点.wav"]
            ];
        } elseif ($train['status'] === '晚点') {
            $audio_queue[] = [
                'type' => 'late',
                'train_number' => $train['number'],
                'files' => ["music/{$train['number']}.wav", "music/晚点.wav"]
            ];
        }
    }
}

// 按出发时间排序
usort($visible_trains, function($a, $b) {
    return strcmp($a['departure'], $b['departure']);
});
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $station_name; ?> - 列车信息</title>
    <style>
        /* 样式部分保持不变 */
        body {
            font-family: 'Microsoft YaHei', Arial, sans-serif;
            background: #003366;
            color: white;
            height: 100vh;
            margin: 0;
            padding: 0;
            overflow: hidden;
        }
        
        .container {
            display: flex;
            flex-direction: column;
            height: 100vh;
        }
        
        .header {
            background: #000;
            text-align: center;
            padding: 15px 0;
            font-size: 24px;
        }
        
        .train-table {
            flex: 1;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        
        .table-header {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            background: #000;
            color: white;
            font-weight: bold;
            padding: 15px;
            text-align: center;
        }
        
        .table-body {
            flex: 1;
            overflow-y: auto;
        }
        
        .table-row {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            background: #0066cc;
            padding: 15px;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .status-early {
            color: #00ff00;
            font-weight: bold;
        }
        
        .status-late {
            color: #ff0000;
            font-weight: bold;
        }
        
        .boarding-now {
            color: #00ff00;
            font-weight: bold;
        }
        
        .boarding-ended {
            color: #ff0000;
            font-weight: bold;
        }
        
        .welcome {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100%;
            font-size: 36px;
            font-weight: bold;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header"><?php echo $station_name; ?>列车信息显示屏</div>
        
        <?php if (empty($visible_trains)): ?>
            <div class="welcome"><?php echo $station_name; ?>欢迎您</div>
        <?php else: ?>
            <div class="train-table">
                <div class="table-header">
                    <div>车次</div>
                    <div>出发地</div>
                    <div>目的地</div>
                    <div>出发时间</div>
                    <div>状态</div>
                </div>
                
                <div class="table-body">
                    <?php foreach ($visible_trains as $train): ?>
                    <div class="table-row">
                        <div><?php echo htmlspecialchars($train['number']); ?></div>
                        <div><?php echo htmlspecialchars($train['origin']); ?></div>
                        <div><?php echo htmlspecialchars($train['destination']); ?></div>
                        <div><?php echo htmlspecialchars($train['departure']); ?></div>
                        <div class="<?php 
                            if ($train['status'] === '早点') echo 'status-early';
                            elseif ($train['status'] === '晚点') echo 'status-late';
                        ?>">
                            <?php echo htmlspecialchars($train['status']); ?>
                            <br>
                            <span class="<?php 
                                if ($train['boarding'] === '正在检票') echo 'boarding-now';
                                elseif ($train['boarding'] === '停止检票') echo 'boarding-ended';
                            ?>">
                                (<?php echo htmlspecialchars($train['boarding']); ?>)
                            </span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- 音频播放器 -->
    <audio id="audio-player"></audio>
    
    <script>
        // 音频队列
        let audioQueue = <?php echo json_encode($audio_queue); ?>;
        let isPlaying = false;
        let currentQueue = [];
        
        // 播放音频队列
        function playAudioQueue() {
            if (currentQueue.length === 0 || isPlaying) return;
            
            isPlaying = true;
            const audioPlayer = document.getElementById('audio-player');
            const nextAudio = currentQueue.shift();
            
            audioPlayer.src = nextAudio;
            audioPlayer.play().catch(e => console.log('音频播放失败:', e));
            
            audioPlayer.onended = function() {
                isPlaying = false;
                setTimeout(playAudioQueue, 500); // 音频间间隔500毫秒
            };
        }
        
        // 处理音频队列
        function processAudioQueue() {
            if (audioQueue.length === 0) return;
            
            const nextAudioSet = audioQueue.shift();
            currentQueue = nextAudioSet.files;
            playAudioQueue();
        }
        
        // 初始处理
        processAudioQueue();
        
        // 每分钟检查一次
        setInterval(() => {
            // 刷新页面获取最新数据
            fetch(window.location.href, {
                headers: { 'Cache-Control': 'no-cache' }
            })
            .then(response => response.text())
            .then(html => {
                const newDoc = new DOMParser().parseFromString(html, 'text/html');
                document.body.innerHTML = newDoc.body.innerHTML;
            });
        }, 60000);
    </script>
</body>
</html>
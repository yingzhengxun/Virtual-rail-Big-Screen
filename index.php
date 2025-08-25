<?php
// 读取车站名称
$station_name = file_exists('name.txt') ? trim(file_get_contents('name.txt')) : '虚拟车站';

// 读取车次数据
$trains = [];
if (file_exists('data.json')) {
    $data = file_get_contents('data.json');
    $trains = json_decode($data, true) ?: [];
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $station_name; ?> - 列车信息</title>
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
            height: 100vh;
            width: 100vw;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }
        
        .header {
            background: #000;
            text-align: center;
            padding: 1.5vh 0;
            font-size: 4vh;
            font-weight: bold;
            position: relative;
            z-index: 10;
            box-shadow: 0 2px 10px rgba(0,0,0,0.5);
        }
        
        .content {
            flex: 1;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            position: relative;
        }
        
        .info-container {
            flex: 1;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        
        .info-header {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            background: #000;
            font-weight: bold;
            padding: 1.5vh 1vw;
            text-align: center;
            font-size: 3vh;
        }
        
        .info-rows {
            flex: 1;
            overflow-y: auto;
            scrollbar-width: none;
        }
        
        .info-rows::-webkit-scrollbar {
            display: none;
        }
        
        .info-row {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            background: #0066cc;
            padding: 1.5vh 1vw;
            text-align: center;
            font-size: 2.8vh;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .boarding-status {
            font-weight: bold;
        }
        
        .boarding-now {
            color: #00ff00;
        }
        
        .boarding-ended {
            color: #ff0000;
        }
        
        .boarding-not-started {
            color: #ffffff;
        }
        
        .status-early {
            color: #00ff00;
        }
        
        .status-late {
            color: #ff0000;
        }
        
        .welcome-message {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100%;
            font-size: 8vh;
            font-weight: bold;
            text-align: center;
            background: #0066cc;
            padding: 0 5vw;
        }
        
        /* 响应式调整 */
        @media (max-aspect-ratio: 4/3) {
            .info-header, .info-row {
                font-size: 2.5vh;
            }
        }
        
        @media (max-aspect-ratio: 1/1) {
            .info-header, .info-row {
                font-size: 2vh;
            }
            .header {
                font-size: 3.5vh;
            }
        }
        
        @media (max-aspect-ratio: 3/4) {
            .info-header, .info-row {
                grid-template-columns: repeat(4, 1fr);
            }
            .info-header div:nth-child(5),
            .info-header div:nth-child(6),
            .info-header div:nth-child(7),
            .info-row div:nth-child(5),
            .info-row div:nth-child(6),
            .info-row div:nth-child(7) {
                display: none;
            }
        }
        
        @media (max-aspect-ratio: 1/2) {
            .info-header, .info-row {
                grid-template-columns: repeat(2, 1fr);
            }
            .info-header div:nth-child(3),
            .info-header div:nth-child(4),
            .info-row div:nth-child(3),
            .info-row div:nth-child(4) {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="header"><?php echo $station_name; ?>列车信息显示屏</div>
    
    <div class="content">
        <div class="info-container">
            <?php if (empty($trains)): ?>
                <div class="welcome-message"><?php echo $station_name; ?>欢迎您</div>
            <?php else: ?>
                <div class="info-header">
                    <div>车次</div>
                    <div>出发地</div>
                    <div>目的地</div>
                    <div>出发时间</div>
                    <div>到达时间</div>
                    <div>状态</div>
                    <div>检票状态</div>
                </div>
                
                <div class="info-rows">
                    <?php foreach ($trains as $train): 
                        $boardingClass = '';
                        $statusClass = '';
                        if (isset($train['boarding'])) {
                            if ($train['boarding'] === '正在检票') {
                                $boardingClass = 'boarding-now';
                            } elseif ($train['boarding'] === '停止检票') {
                                $boardingClass = 'boarding-ended';
                            } elseif ($train['boarding'] === '未开始') {
                                $boardingClass = 'boarding-not-started';
                            }
                        }
                        if (isset($train['status'])) {
                            if ($train['status'] === '早点') {
                                $statusClass = 'status-early';
                            } elseif ($train['status'] === '晚点') {
                                $statusClass = 'status-late';
                            }
                        }
                    ?>
                    <div class="info-row">
                        <div><?php echo htmlspecialchars($train['number'] ?? ''); ?></div>
                        <div><?php echo htmlspecialchars($train['origin'] ?? ''); ?></div>
                        <div><?php echo htmlspecialchars($train['destination'] ?? ''); ?></div>
                        <div><?php echo htmlspecialchars($train['departure'] ?? ''); ?></div>
                        <div><?php echo htmlspecialchars($train['arrival'] ?? ''); ?></div>
                        <div class="<?php echo $statusClass; ?>"><?php echo htmlspecialchars($train['status'] ?? ''); ?></div>
                        <div class="boarding-status <?php echo $boardingClass; ?>">
                            <?php echo htmlspecialchars($train['boarding'] ?? '未设置'); ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- 音频播放器 -->
    <audio id="audio-player"></audio>
    
    <script>
        // 音频队列
        let audioQueue = [];
        let isPlaying = false;
        
        // 音频文件映射
        const audioFiles = {
            '开检': 'music/开检.wav',
            '停检': 'music/停检.wav',
            '早点': 'music/早点.wav',
            '晚点': 'music/晚点.wav',
            '乘客你好': 'music/乘客你好.wav'
        };
        
        // 上次播放状态记录
        let lastPlayed = {
            boarding: {},
            status: {}
        };
        
        // 播放音频队列
        function playAudioQueue() {
            if (audioQueue.length === 0 || isPlaying) return;
            
            isPlaying = true;
            const audioPlayer = document.getElementById('audio-player');
            const nextAudio = audioQueue.shift();
            
            audioPlayer.src = nextAudio;
            audioPlayer.play().catch(e => console.log('音频播放失败:', e));
            
            audioPlayer.onended = function() {
                isPlaying = false;
                setTimeout(playAudioQueue, 300); // 音频间间隔300毫秒
            };
        }
        
        // 添加音频到队列
        function addToAudioQueue(audioFile) {
            if (audioFile) {
                audioQueue.push(audioFile);
                if (!isPlaying) {
                    playAudioQueue();
                }
            }
        }
        
        // 检查并播放音频
        function checkAndPlayAudio() {
            const trains = <?php echo json_encode($trains); ?>;
            
            trains.forEach(train => {
                const trainNumber = train.number || '';
                const trainAudio = `music/${trainNumber}.wav`;
                
                // 检查检票状态
                if (train.boarding && train.boarding !== lastPlayed.boarding[trainNumber]) {
                    if (train.boarding === '正在检票') {
                        addToAudioQueue(trainAudio);
                        addToAudioQueue(audioFiles['开检']);
                    } else if (train.boarding === '停止检票') {
                        addToAudioQueue(trainAudio);
                        addToAudioQueue(audioFiles['停检']);
                    }
                    lastPlayed.boarding[trainNumber] = train.boarding;
                }
                
                // 检查列车状态
                if (train.status && train.status !== lastPlayed.status[trainNumber]) {
                    if (train.status === '早点') {
                        addToAudioQueue(audioFiles['乘客你好']);
                        addToAudioQueue(trainAudio);
                        addToAudioQueue(audioFiles['早点']);
                    } else if (train.status === '晚点') {
                        addToAudioQueue(audioFiles['乘客你好']);
                        addToAudioQueue(trainAudio);
                        addToAudioQueue(audioFiles['晚点']);
                    }
                    lastPlayed.status[trainNumber] = train.status;
                }
            });
        }
        
        // 初始检查
        checkAndPlayAudio();
        
        // 每分钟检查一次
        setInterval(checkAndPlayAudio, 60000);
        
        // 动态调整字体大小以适应宽高比
        function adjustFontSize() {
            const aspectRatio = window.innerWidth / window.innerHeight;
            const baseSize = Math.min(window.innerWidth, window.innerHeight);
            
            // 根据宽高比调整字体大小
            if (aspectRatio > 2) {
                document.documentElement.style.fontSize = (baseSize / 100) + 'px';
            } else if (aspectRatio > 1.5) {
                document.documentElement.style.fontSize = (baseSize / 90) + 'px';
            } else {
                document.documentElement.style.fontSize = (baseSize / 80) + 'px';
            }
        }
        
        // 初始调整
        adjustFontSize();
        
        // 窗口大小变化时调整
        window.addEventListener('resize', adjustFontSize);
    </script>
</body>
</html>
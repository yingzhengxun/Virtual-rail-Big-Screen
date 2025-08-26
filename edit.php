<?php
// 错误报告开启（开发环境）
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $trains = [];
    
    if (isset($_POST['number']) && is_array($_POST['number'])) {
        $count = count($_POST['number']);
        for ($i = 0; $i < $count; $i++) {
            if (!empty(trim($_POST['number'][$i]))) {
                $trains[] = [
                    'number' => $_POST['number'][$i],
                    'origin' => $_POST['origin'][$i],
                    'destination' => $_POST['destination'][$i],
                    'departure' => $_POST['departure'][$i],
                    'arrival' => $_POST['arrival'][$i],
                    'status' => $_POST['status'][$i]
                    // 移除了boarding字段
                ];
            }
        }
    }
    
    // 保存到文件
    $result = file_put_contents('data.json', json_encode($trains, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    
    // 处理站点名称
    if (isset($_POST['station_name'])) {
        file_put_contents('name.txt', trim($_POST['station_name']));
    }
    
    if ($result !== false) {
        // 重定向回编辑页面
        header('Location: edit.php?success=1');
        exit;
    } else {
        $error = "保存失败，请检查文件权限";
    }
}

// 读取现有数据
$trains = [];
if (file_exists('data.json')) {
    $data_content = file_get_contents('data.json');
    if ($data_content !== false) {
        $trains_data = json_decode($data_content, true);
        if (is_array($trains_data)) {
            $trains = $trains_data;
        }
    }
}

// 读取车站名称
$station_name = '虚拟车站';
if (file_exists('name.txt')) {
    $name_content = file_get_contents('name.txt');
    if ($name_content !== false) {
        $station_name = trim($name_content);
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>编辑车次信息 - <?php echo htmlspecialchars($station_name); ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 1400px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        h1 {
            text-align: center;
            color: #003366;
            margin-bottom: 20px;
        }
        .station-name-form {
            background: #f0f8ff;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #003366;
        }
        .form-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }
        .train-form {
            margin-bottom: 20px;
        }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr 2fr 1fr 1fr 1fr;
            gap: 10px;
            margin-bottom: 10px;
        }
        .form-header {
            font-weight: bold;
            background-color: #000;
            color: white;
            padding: 10px;
            border-radius: 4px;
        }
        input, select {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            width: 100%;
        }
        button {
            background-color: #0066cc;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 4px;
            cursor: pointer;
            margin-right: 10px;
            font-size: 16px;
        }
        button.remove {
            background-color: #cc0000;
        }
        .actions {
            margin-top: 20px;
            text-align: center;
        }
        .success {
            background-color: #d4edda;
            color: #155724;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
            text-align: center;
        }
        .error {
            background-color: #f8d7da;
            color: #721c24;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>编辑车次信息 - <?php echo htmlspecialchars($station_name); ?></h1>
        
        <?php if (isset($_GET['success'])): ?>
            <div class="success">车次信息已成功更新！</div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <!-- 站点名称编辑部分 -->
            <div class="station-name-form">
                <h2>编辑站点名称</h2>
                <div class="form-group">
                    <label for="station_name">站点名称:</label>
                    <input type="text" id="station_name" name="station_name" 
                           value="<?php echo htmlspecialchars($station_name); ?>" 
                           placeholder="请输入站点名称">
                </div>
            </div>
            
            <!-- 车次信息编辑部分 -->
            <div class="train-form">
                <h2>编辑车次信息</h2>
                <div class="form-row form-header">
                    <div>车次</div>
                    <div>出发地</div>
                    <div>目的地</div>
                    <div>出发时间</div>
                    <div>到达时间</div>
                    <div>状态</div>
                </div>
                
                <div id="train-rows">
                    <?php if (!empty($trains)): ?>
                        <?php foreach ($trains as $index => $train): ?>
                        <div class="form-row">
                            <input type="text" name="number[]" value="<?php echo htmlspecialchars($train['number'] ?? ''); ?>" placeholder="G101">
                            <input type="text" name="origin[]" value="<?php echo htmlspecialchars($train['origin'] ?? ''); ?>" placeholder="北京">
                            <input type="text" name="destination[]" value="<?php echo htmlspecialchars($train['destination'] ?? ''); ?>" placeholder="上海">
                            <input type="text" name="departure[]" value="<?php echo htmlspecialchars($train['departure'] ?? ''); ?>" placeholder="08:00">
                            <input type="text" name="arrival[]" value="<?php echo htmlspecialchars($train['arrival'] ?? ''); ?>" placeholder="12:30">
                            <input type="text" name="status[]" value="<?php echo htmlspecialchars($train['status'] ?? ''); ?>" placeholder="正点">
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="form-row">
                            <input type="text" name="number[]" placeholder="G101">
                            <input type="text" name="origin[]" placeholder="北京">
                            <input type="text" name="destination[]" placeholder="上海">
                            <input type="text" name="departure[]" placeholder="08:00">
                            <input type="text" name="arrival[]" placeholder="12:30">
                            <input type="text" name="status[]" placeholder="正点">
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="actions">
                <button type="button" onclick="addRow()">添加车次</button>
                <button type="button" onclick="removeRow()" class="remove">删除最后一行</button>
                <button type="submit">保存更改</button>
            </div>
        </form>
    </div>

    <script>
        function addRow() {
            const row = document.createElement('div');
            row.className = 'form-row';
            row.innerHTML = `
                <input type="text" name="number[]" placeholder="G101">
                <input type="text" name="origin[]" placeholder="北京">
                <input type="text" name="destination[]" placeholder="上海">
                <input type="text" name="departure[]" placeholder="08:00">
                <input type="text" name="arrival[]" placeholder="12:30">
                <input type="text" name="status[]" placeholder="正点">
            `;
            document.getElementById('train-rows').appendChild(row);
        }
        
        function removeRow() {
            const rows = document.getElementById('train-rows');
            if (rows.children.length > 1) {
                rows.removeChild(rows.lastChild);
            }
        }
    </script>
</body>
</html>
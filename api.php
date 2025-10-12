<?php
// Alist配置参数
define('ALIST_LOGIN_ENABLED', false);  // 是否启用Alist登录功能 (true:启用 false:禁用)
define('ALIST_URL', 'http://127.0.0.1:5244');  // Alist服务地址
define('ALIST_USERNAME', 'user');  // Alist用户名
define('ALIST_PASSWORD', 'password');  // Alist密码

define('TOKEN_FILE', __DIR__ . '/alist_token.php');

function getOrRefreshToken($forceRefresh = false) {
    // 如果未启用Alist登录，直接返回null
    if (!ALIST_LOGIN_ENABLED) {
        return null;
    }
    
    if (!$forceRefresh && file_exists(TOKEN_FILE)) {
        $token = include TOKEN_FILE;
        if (!empty($token) && is_string($token)) {
            return trim($token); 
        }
    }
    
    $login_url = ALIST_URL . '/api/auth/login';
    $login_data = [
        'username' => ALIST_USERNAME,
        'password' => ALIST_PASSWORD
    ];
    
    $ch = curl_init($login_url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($login_data),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
        ],
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_TIMEOUT => 30,
    ]);
    
    $login_response = curl_exec($ch);
    $login_info = json_decode($login_response, true);
    curl_close($ch);
    
    if (!$login_info || !isset($login_info['data']['token'])) {
        if (file_exists(TOKEN_FILE)) {
            unlink(TOKEN_FILE);
        }
        return null;
    }
    
    $token = $login_info['data']['token'];  
    $token_content = "<?php\nreturn '" . addslashes($token) . "';\n?>";
    file_put_contents(TOKEN_FILE, $token_content, LOCK_EX);      
    return $token;
}

function getFileInfo($file_path) {
    $api_url = ALIST_URL . '/api/fs/get';
    
    // 如果启用了Alist登录，获取token
    if (ALIST_LOGIN_ENABLED) {
        $token = getOrRefreshToken();
        if (!$token) {
            return null;
        }
        
        $headers = [
            'Content-Type: application/json',
            'Authorization: ' . $token,
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
        ];
    } else {
        // 未启用登录，不添加Authorization头
        $headers = [
            'Content-Type: application/json',
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
        ];
    }
    
    $file_data = ['path' => $file_path];
    
    $ch = curl_init($api_url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($file_data),
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_TIMEOUT => 30,
    ]);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    $data = json_decode($response, true);
    
    // 如果启用了登录且第一次请求失败，尝试刷新token重试
    if (ALIST_LOGIN_ENABLED && (!$data || $data['code'] != 200)) {
        $new_token = getOrRefreshToken(true);
        if (!$new_token) {
            return null;
        }
        
        $headers = [
            'Content-Type: application/json',
            'Authorization: ' . $new_token,
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
        ];
        
        $ch = curl_init($api_url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($file_data),
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT => 30,
        ]);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        $data = json_decode($response, true);
        
        if (!$data || $data['code'] != 200) {
            return null;
        }
    } elseif (!$data || $data['code'] != 200) {
        // 未启用登录且请求失败
        return null;
    }
    
    return $data['data'];
}

if (isset($_GET['file']) && !empty($_GET['file'])) {
    $file_path = $_GET['file'];
    $file_info = getFileInfo($file_path);
    
    if ($file_info && isset($file_info['raw_url'])) {
        header('Location: ' . $file_info['raw_url']);
        exit;
    } else {
        header('HTTP/1.1 404 Not Found');
        echo '文件不存在或无法访问';
        exit;
    }
}

echo '请提供文件路径参数，例如：?file=路径/文件名';
?>
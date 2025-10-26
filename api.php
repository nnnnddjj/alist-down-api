<?php
// Alist配置参数
define('ALIST_LOGIN_ENABLED', false);  // 是否启用Alist登录功能 (true:启用 false:禁用)
define('ALIST_URL', 'http://127.0.0.1:5244');  // alist地址
define('ALIST_USERNAME', 'user');  // 用户名
define('ALIST_PASSWORD', 'password');  // 密码
define('TOKEN_FILE', __DIR__ . '/alist_token.php');

function getOrRefreshToken($forceRefresh = false) {
    if (!ALIST_LOGIN_ENABLED) return null;
    
    if (!$forceRefresh && file_exists(TOKEN_FILE)) {
        $token = include TOKEN_FILE;
        if (!empty($token) && is_string($token)) {
            return trim($token); 
        }
    }
    
    $login_url = ALIST_URL . '/api/auth/login';
    $login_data = json_encode([
        'username' => ALIST_USERNAME,
        'password' => ALIST_PASSWORD
    ]);
    
    $ch = curl_init($login_url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $login_data,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_TIMEOUT => 30,
    ]);
    
    $response = curl_exec($ch);
    $login_info = json_decode($response, true);
    curl_close($ch);
    
    if (empty($login_info['data']['token'])) {
        if (file_exists(TOKEN_FILE)) unlink(TOKEN_FILE);
        return null;
    }
    
    $token = $login_info['data']['token'];
    file_put_contents(TOKEN_FILE, "<?php\nreturn '" . addslashes($token) . "';\n?>", LOCK_EX);
    return $token;
}

function getFileInfo($file_path) {
    $api_url = ALIST_URL . '/api/fs/get';
    
    $headers = ['Content-Type: application/json'];
    if (ALIST_LOGIN_ENABLED) {
        $token = getOrRefreshToken();
        if (!$token) return null;
        $headers[] = 'Authorization: ' . $token;
    }
    
    $ch = curl_init($api_url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode(['path' => $file_path]),
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_TIMEOUT => 30,
    ]);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    $data = json_decode($response, true);
    
    if (!empty($data['data']) && $data['code'] == 200) {
        return $data['data'];
    }
    
    // 登录模式下重试
    if (ALIST_LOGIN_ENABLED) {
        $token = getOrRefreshToken(true);
        if ($token) {
            $headers = [
                'Content-Type: application/json',
                'Authorization: ' . $token
            ];
            
            $ch = curl_init($api_url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode(['path' => $file_path]),
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_TIMEOUT => 30,
            ]);
            
            $response = curl_exec($ch);
            curl_close($ch);
            
            $data = json_decode($response, true);
            if (!empty($data['data']) && $data['code'] == 200) {
                return $data['data'];
            }
        }
    }
    
    return null;
}

if (!empty($_GET['file'])) {
    $file_info = getFileInfo($_GET['file']);
    if (!empty($file_info['raw_url'])) {
        header('Location: ' . $file_info['raw_url']);
        exit;
    }
}

echo '请提供文件路径参数，例如：?file=路径/文件名';
?>
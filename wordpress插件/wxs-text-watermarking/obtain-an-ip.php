<?php
/**
 * 天无神话
 * 获取访客的IP地址
 * 
 * 该函数尝试从多种来源检测客户端IP地址，包括HTTP头信息和服务器变量
 * 并通过正则表达式验证IP格式的有效性
 * @return string 访客的IP地址，如果无法获取则返回空字符串
 */
function wxs_get_ip() {
    if (getenv('HTTP_CLIENT_IP') && strcasecmp(getenv('HTTP_CLIENT_IP'), 'unknown')) {
        $ip = getenv('HTTP_CLIENT_IP');
    } elseif (getenv('HTTP_X_FORWARDED_FOR') && strcasecmp(getenv('HTTP_X_FORWARDED_FOR'), 'unknown')) {
        $ip = getenv('HTTP_X_FORWARDED_FOR');
    } elseif (getenv('REMOTE_ADDR') && strcasecmp(getenv('REMOTE_ADDR'), 'unknown')) {
        $ip = getenv('REMOTE_ADDR');
    } elseif (isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] && strcasecmp($_SERVER['REMOTE_ADDR'], 'unknown')) {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    
    // 使用正则表达式验证IP地址格式
    return preg_match('/[\d\.]{7,15}/', $ip, $matches) ? $matches[0] : '';
}

// 获取访客IP地址
$visitor_ip = wxs_get_ip();

// 构建JSON响应数据
$response = [
    'success' => !empty($visitor_ip),
    'ip' => $visitor_ip,
    'timestamp' => time(),
    'server' => $_SERVER['SERVER_NAME'] ?? 'unknown',
    'method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown'
];

// 设置响应头为JSON格式
header('Content-Type: application/json');

// 输出JSON数据
echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
?>    

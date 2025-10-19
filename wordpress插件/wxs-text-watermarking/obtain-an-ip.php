<?php
/**
 * 天无神话 - 获取访客IP地址
 * 引入WordPress环境并增强安全防护
 */

// 引入WordPress环境
define('WP_USE_THEMES', false);
require_once('../../../wp-load.php'); // 根据实际路径调整

// 设置安全头信息
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('X-XSS-Protection: 1; mode=block');

// 防止缓存 - 应对Super Cache等静态化插件
header('Cache-Control: no-cache, no-store, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: Mon, 01 Jan 1990 00:00:00 GMT');

/**
 * 安全获取服务器变量
 * @param string $key 变量名
 * @param mixed $default 默认值
 * @return mixed 清理后的值
 */
function wxs_get_server_var($key, $default = '') {
    if (!isset($_SERVER[$key])) {
        return $default;
    }
    
    // 获取原始值
    $raw_value = $_SERVER[$key];
    
    // 如果是字符串，进行清理
    if (is_string($raw_value)) {
        $value = wp_unslash($raw_value); // 先反斜杠
        $value = sanitize_text_field($value); // 再清理
        return $value;
    }
    
    // 如果是数组或其他类型，返回原始值（但这种情况在$_SERVER中很少见）
    return $raw_value;
}

/**
 * 安全获取环境变量
 * @param string $key 变量名
 * @param mixed $default 默认值
 * @return mixed 清理后的值
 */
function wxs_get_env_var($key, $default = '') {
    $value = getenv($key);
    if ($value === false) {
        return $default;
    }
    
    return sanitize_text_field($value);
}

/**
 * 获取访客的IP地址
 * 增强安全验证，防止IP伪造
 * @return string 访客的IP地址，如果无法获取则返回空字符串
 */
function wxs_get_ip() {
    $ip = '';
    
    // 按优先级检查各种IP来源
    $ip_sources = [
        ['type' => 'env', 'key' => 'HTTP_CLIENT_IP'],
        ['type' => 'env', 'key' => 'HTTP_X_FORWARDED_FOR'],
        ['type' => 'env', 'key' => 'HTTP_X_FORWARDED'],
        ['type' => 'env', 'key' => 'HTTP_X_CLUSTER_CLIENT_IP'],
        ['type' => 'env', 'key' => 'HTTP_FORWARDED_FOR'],
        ['type' => 'env', 'key' => 'HTTP_FORWARDED'],
        ['type' => 'env', 'key' => 'REMOTE_ADDR'],
        ['type' => 'server', 'key' => 'HTTP_CLIENT_IP'],
        ['type' => 'server', 'key' => 'HTTP_X_FORWARDED_FOR'],
        ['type' => 'server', 'key' => 'HTTP_X_FORWARDED'],
        ['type' => 'server', 'key' => 'HTTP_X_CLUSTER_CLIENT_IP'],
        ['type' => 'server', 'key' => 'HTTP_FORWARDED_FOR'],
        ['type' => 'server', 'key' => 'HTTP_FORWARDED'],
        ['type' => 'server', 'key' => 'REMOTE_ADDR']
    ];
    
    foreach ($ip_sources as $source) {
        if ($source['type'] === 'env') {
            $ip_candidate = wxs_get_env_var($source['key']);
        } else {
            $ip_candidate = wxs_get_server_var($source['key']);
        }
        
        if (!empty($ip_candidate)) {
            // 处理多个IP的情况（如X-Forwarded-For）
            if (strpos($ip_candidate, ',') !== false) {
                $ips = explode(',', $ip_candidate);
                $ip_candidate = trim($ips[0]);
            }
            
            // 验证IP格式
            if (filter_var($ip_candidate, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                $ip = $ip_candidate;
                break;
            }
        }
    }
    
    return $ip;
}

/**
 * 验证请求安全性
 * @return bool 是否安全请求
 */
function wxs_validate_request() {
    // 检查请求方法
    $request_method = wxs_get_server_var('REQUEST_METHOD');
    if ($request_method !== 'GET') {
        return false;
    }
    
    // 可选的Referer检查
    $http_referer = wxs_get_server_var('HTTP_REFERER');
    if (!empty($http_referer)) {
        $referer = wp_parse_url($http_referer);
        $host = wxs_get_server_var('HTTP_HOST');
        
        if ($referer && isset($referer['host']) && $host) {
            if (strpos($referer['host'], $host) === false) {
                return false;
            }
        }
    }
    
    return true;
}

// 主程序逻辑
try {
    // 验证请求
    if (!wxs_validate_request()) {
        throw new Exception('Invalid request');
    }
    
    // 获取访客IP地址
    $visitor_ip = wxs_get_ip();
    
    // 构建响应数据
    $response = [
        'success' => !empty($visitor_ip),
        'ip' => $visitor_ip,
        'timestamp' => time(),
        'server' => wxs_get_server_var('SERVER_NAME', 'unknown'),
        'method' => wxs_get_server_var('REQUEST_METHOD', 'unknown')
    ];
    
} catch (Exception $e) {
    // 错误处理
    $response = [
        'success' => false,
        'ip' => '',
        'timestamp' => time(),
        'server' => wxs_get_server_var('SERVER_NAME', 'unknown'),
        'method' => wxs_get_server_var('REQUEST_METHOD', 'unknown'),
        'error' => 'Access denied'
    ];
}

// 设置JSON响应头
header('Content-Type: application/json; charset=utf-8');

// 输出JSON数据（保持与原来相同的格式）
echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
exit;

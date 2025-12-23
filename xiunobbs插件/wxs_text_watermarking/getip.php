<?php
/**
 * 文本盲水印插件 - IP获取接口
 *
 * @create 2025-12-23
 * @author 天无神话 https://wxsnote.cn
 * @GitHub开源地址 https://github.com/twsh0305/text_watermarking
 */

// 设置响应头
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');

// 定义常量
if (!defined('DEBUG')) {
    define('DEBUG', 0);
}

// 获取IP的多种方式（优先级从高到低）
function get_client_ip() {
    $ip = 'unknown';
    
    // 尝试从HTTP头获取
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        // 如果使用代理，取第一个IP
        $ip_list = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        $ip = trim($ip_list[0]);
    } elseif (!empty($_SERVER['HTTP_X_REAL_IP'])) {
        $ip = $_SERVER['HTTP_X_REAL_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED'];
    } elseif (!empty($_SERVER['HTTP_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_FORWARDED_FOR'];
    } elseif (!empty($_SERVER['HTTP_FORWARDED'])) {
        $ip = $_SERVER['HTTP_FORWARDED'];
    }
    // 如果没有通过代理，直接获取REMOTE_ADDR
    elseif (!empty($_SERVER['REMOTE_ADDR'])) {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    
    // 验证IP格式
    if (!filter_var($ip, FILTER_VALIDATE_IP)) {
        $ip = 'unknown';
    }
    
    return $ip;
}

// 获取IP
$ip = get_client_ip();

// 返回JSON响应
echo json_encode(array(
    'success' => true,
    'ip' => $ip
));
exit;
?>
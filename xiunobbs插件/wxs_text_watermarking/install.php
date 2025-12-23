<?php
/**
 * 文本盲水印插件安装文件
 *
 * @create 2025-12-23
 * @author 天无神话 https://wxsnote.cn
 * @GitHub开源地址 https://github.com/twsh0305/text_watermarking
 */

!defined('DEBUG') and exit('Forbidden');

// 插件配置项默认值
$default_config = array(
    'enable' => 1,
    'min_paragraph_length' => 15,
    'insert_method' => 1,
    'random_count_type' => 1,
    'random_custom_count' => 1,
    'random_word_ratio' => 400,
    'fixed_interval' => 20,
    'debug_mode' => 0,
    'include_ip' => 1,
    'include_user' => 1,
    'include_time' => 1,
    'include_custom' => 1,
    'custom_text' => '本站内容版权所有',
    'include_author' => 1,
    'include_thread' => 1,
    'html_tags' => 'p,li',
    'bot_ua' => "Baiduspider\nGooglebot\nbingbot\nYahoo! Slurp\n360Spider\nSogou web spider\nYisouSpider"
);

// 初始化配置
$current_config = kv_get('wxs_text_watermarking');
if (!$current_config) {
    kv_set('wxs_text_watermarking', $default_config);
} else {
    // 合并配置，确保新增的配置项有默认值
    $merged_config = array_merge($default_config, $current_config);
    kv_set('wxs_text_watermarking', $merged_config);
}

// 创建必要的目录结构
$plugin_dir = APP_PATH . 'plugin/wxs_text_watermarking/';
$dirs = array('view', 'view/htm','assets','assets/js','hook');
foreach ($dirs as $dir) {
    $full_dir = $plugin_dir . $dir;
    if (!is_dir($full_dir)) {
        mkdir($full_dir, 0755, true);
    }
}

// 返回安装成功信息
message(0, '文本盲水印插件安装成功！');
?>
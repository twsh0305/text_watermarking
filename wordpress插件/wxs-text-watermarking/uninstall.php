<?php
/**
 * 文本盲水印插件卸载脚本
 * 
 * 当用户删除插件时，此文件会被WordPress自动调用。
 */

// 如果 uninstall.php 不是由 WordPress 调用，则退出
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// 记录开始卸载
error_log('【文本盲水印】通过uninstall.php开始卸载...');

// 删除插件存储的所有选项数据
$options_to_delete = [
    'wxs_watermark_init_csf_options'
];

foreach ($options_to_delete as $option) {
    $value = get_option($option);
    if ($value !== false) {
        $result = delete_option($option);
        if ($result) {
            error_log("成功删除选项: {$option}");
        } else {
            error_log("删除选项失败: {$option}");
        }
    } else {
        error_log("选项不存在，跳过: {$option}");
    }
}

error_log('文本盲水印插件通过uninstall.php卸载完成');
?>
    

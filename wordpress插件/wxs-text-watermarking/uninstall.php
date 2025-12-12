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
// 删除当前插件配置
delete_option('wxstbw_init_csf_options');
// 删除历史配置
delete_option('wxs_watermark_init_csf_options');
?>

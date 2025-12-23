<?php
/**
 * 文本盲水印插件卸载文件
 *
 * @create 2025-12-23
 * @author 天无神话 https://wxsnote.cn
 * @GitHub开源地址 https://github.com/twsh0305/text_watermarking
 */
!defined('DEBUG') AND exit('Forbidden');

// 删除插件配置
kv_cache_delete('wxs_text_watermarking');
?>
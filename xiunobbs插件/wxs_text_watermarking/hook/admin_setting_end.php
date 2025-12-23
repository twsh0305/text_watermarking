<?php exit;

elseif($action == 'wxstbw') {
    
    if($method == 'GET') {
        
        // 读取配置
        $config = kv_get('wxs_text_watermarking');
        
        // 准备表单字段
        $input = array();
        
        // 基础设置
        $input['enable'] = form_radio_yes_no('enable', isset($config['enable']) ? $config['enable'] : 1);
        $input['min_paragraph_length'] = form_text('min_paragraph_length', isset($config['min_paragraph_length']) ? $config['min_paragraph_length'] : 15, 20);
        $input['insert_method'] = form_select('insert_method', 
            array(
                '1' => '段落末尾插入',
                '2' => '随机位置插入（推荐）',
                '3' => '固定字符间隔插入'
            ), 
            isset($config['insert_method']) ? $config['insert_method'] : 1
        );
        $input['random_count_type'] = form_select('random_count_type', 
            array(
                '1' => '自定义插入数量',
                '2' => '按字数比例插入'
            ), 
            isset($config['random_count_type']) ? $config['random_count_type'] : 1
        );
        $input['random_custom_count'] = form_text('random_custom_count', isset($config['random_custom_count']) ? $config['random_custom_count'] : 1, 20);
        $input['random_word_ratio'] = form_text('random_word_ratio', isset($config['random_word_ratio']) ? $config['random_word_ratio'] : 400, 20);
        $input['fixed_interval'] = form_text('fixed_interval', isset($config['fixed_interval']) ? $config['fixed_interval'] : 20, 20);
        $input['debug_mode'] = form_radio_yes_no('debug_mode', isset($config['debug_mode']) ? $config['debug_mode'] : 0);
        
        // 水印内容设置
        $input['include_ip'] = form_radio_yes_no('include_ip', isset($config['include_ip']) ? $config['include_ip'] : 1);
        $input['include_user'] = form_radio_yes_no('include_user', isset($config['include_user']) ? $config['include_user'] : 1);
        $input['include_author'] = form_radio_yes_no('include_author', isset($config['include_author']) ? $config['include_author'] : 1); 
        $input['include_thread'] = form_radio_yes_no('include_thread', isset($config['include_thread']) ? $config['include_thread'] : 1);
        $input['include_time'] = form_radio_yes_no('include_time', isset($config['include_time']) ? $config['include_time'] : 1);
        $input['include_custom'] = form_radio_yes_no('include_custom', isset($config['include_custom']) ? $config['include_custom'] : 1);
        $input['custom_text'] = form_text('custom_text', isset($config['custom_text']) ? htmlspecialchars($config['custom_text']) : '本站内容版权所有', 100);
        
        // 高级设置
        $input['html_tags'] = form_text('html_tags', isset($config['html_tags']) ? $config['html_tags'] : 'p,li', 100);
        
        // 爬虫过滤白名单
        $input['bot_ua'] = form_textarea('bot_ua', isset($config['bot_ua']) ? $config['bot_ua'] : "Baiduspider\nGooglebot\nbingbot\nYahoo! Slurp\n360Spider\nSogou web spider\nYisouSpider", '100%', 150);
        
        // 设置页面标题
        $header['title'] = '文本盲水印设置';
        $header['mobile_title'] = '文本盲水印';
        
        // 加载设置页面
        include _include(APP_PATH.'plugin/wxs_text_watermarking/view/htm/setting_wxstbw.htm');
        
    } else {
        
        // 处理POST请求，保存配置
        
        $config = array();
        
        // 基础设置
        $config['enable'] = param('enable', 1);
        $config['min_paragraph_length'] = param('min_paragraph_length', 15);
        $config['insert_method'] = param('insert_method', 1);
        $config['random_count_type'] = param('random_count_type', 1);
        $config['random_custom_count'] = param('random_custom_count', 1);
        $config['random_word_ratio'] = param('random_word_ratio', 400);
        $config['fixed_interval'] = param('fixed_interval', 20);
        $config['debug_mode'] = param('debug_mode', 0);
        
        // 水印内容设置
        $config['include_ip'] = param('include_ip', 1);
        $config['include_user'] = param('include_user', 1);
        $config['include_author'] = param('include_author', 1);
        $config['include_thread'] = param('include_thread', 1);
        $config['include_time'] = param('include_time', 1);
        $config['include_custom'] = param('include_custom', 1);
        $config['custom_text'] = param('custom_text', '', FALSE);
        
        // 高级设置
        $config['html_tags'] = param('html_tags', '', FALSE);
        
        // 爬虫过滤白名单
        $config['bot_ua'] = param('bot_ua', '', FALSE);
        
        // 验证输入
        if ($config['min_paragraph_length'] < 5) {
            message(-1, '最小段落长度不能小于5');
        }
        
        if ($config['insert_method'] == 3 && $config['fixed_interval'] < 5) {
            message(-1, '固定字符间隔不能小于5');
        }
        
        if ($config['random_custom_count'] < 1) {
            message(-1, '自定义插入数量不能小于1');
        }
        
        if ($config['random_word_ratio'] < 50) {
            message(-1, '字数比例不能小于50');
        }
        
        // 保存配置
        kv_set('wxs_text_watermarking', $config);
        
        // 清除缓存
        cache_delete('plugin_wxs_text_watermarking');
        
        // 返回成功消息
        message(0, lang('save_successfully'));
    }
}
?>
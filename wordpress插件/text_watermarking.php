<?php
/**
 * @author: https://github.com/twsh0305
 * @description: 文本水印功能
 * @version: 1.0.0
 * 文章地址：https://wxsnote.cn/
 * 开源地址：https://github.com/twsh0305/text_watermarking
 * 说明：该功能在文章内容中插入盲水印，支持多种插入方式和配置选项
 */

// 将水印配置输出为前端JS全局变量
$wxs_watermark_config = [
    // 是否启用盲水印功能
    // 可选值：1（启用）、0（禁用）
    // 说明：设置为0时，整个水印功能不生效，文本保持原样
    'enable'               => 1,                 

    // 最小段落字数限制（少于此值的段落不插入水印）
    // 可选值：正整数（如10、20、30等）
    // 说明：用于过滤短段落（如短句、小标题），避免在过短文本中插入水印导致易被发现
    // 建议值：15-30（根据内容长度调整）
    'min_paragraph_length' => 20,                

    // 盲水印插入方式（核心控制参数）
    // 可选值：
    // 1 = 段落末尾插入：在每个符合长度的<p>标签文本末尾添加水印
    // 2 = 随机位置插入：在段落文本中随机位置插入，插入点不固定
    // 3 = 固定字数插入：按固定间隔（如每20字）在段落中插入水印
    'insert_method'        => 2,                 

    // 仅当insert_method=2（随机位置插入）时生效的子配置
    'random' => [
        // 随机插入次数的计算模式
        // 可选值：
        // 1 = 自定义次数：每段固定插入指定次数的水印
        // 2 = 字数决定：根据段落字数自动计算插入次数（字数越多，插入次数越多）
        'count_type'        => 2,                
        // 当count_type=1（自定义次数）时生效：每段插入的次数
        // 可选值：正整数（如1、2、3等）
        // 说明：次数越多，水印密度越大，但可能增加被发现的风险
        'custom_count'      => 1,                
        // 当count_type=2（字数决定）时生效：每多少字增加1次插入
        // 可选值：正整数（如200、300、400等）
        // 说明：值越小，插入次数越多（例：400=每400字增加1次，800字段落插入2次）
        'word_based_ratio'  => 400,              
    ],

    // 仅当insert_method=3（固定字数插入）时生效的子配置
    'fixed' => [
        // 固定插入间隔（每多少字插入1次水印）
        // 可选值：正整数（如10、20、50等）
        // 说明：值越小，插入越频繁（例：20=每20字插入1次）
        'interval'          => 20,                
    ],

    // 水印内容组成配置（控制水印包含的信息）
    'watermark_content' => [
        // 是否包含访问者IP地址（基于高级IP获取规则，支持代理场景）
        // 可选值：true（包含）、false（不包含）
        'include_ip'        => true,
        // 是否包含用户ID（适配WordPress用户系统）
        // 可选值：true（包含）、false（不包含）
        // 说明：登录用户显示用户ID，游客显示"guest"
        'include_user'      => true,
        // 是否包含当前时间（水印生成时的时间）
        // 可选值：true（包含）、false（不包含）
        // 格式：YYYY-MM-DD HH:MM:SS（如2023-10-01 15:30:00）
        'include_time'      => true,
        // 是否包含自定义文本（可添加版权声明、标识等）
        // 可选值：true（包含）、false（不包含）
        'include_custom'    => true,
        // 当include_custom=true时生效：自定义文本内容
        // 可选值：任意字符串（建议简短，避免水印过长被发现）
        // 说明：建议包含版权信息（如"XX版权所有"）或独特标识
        'custom_text'       => '王先生笔记 版权所有',
    ],

    // 爬虫UA列表
    'bot_ua' => [
        'googlebot', 'bingbot', 'baiduspider', 'sogou web spider', '360spider', 'yisouspider', 'bytespider', 'duckduckbot', 'yandexbot', 'yahoo! slurp'
    ],
    // 新增：调试模式开关（1=启用，0=禁用）
    'debug_mode'          => 0,     
];
function wxs_output_watermark_config() {
    // 仅在文章页输出配置及未登录时
    if (!is_single() || is_user_logged_in()) {
        return;
    }

    global $wxs_watermark_config;

    // 确保配置数组存在
    if (!isset($wxs_watermark_config) || !is_array($wxs_watermark_config)) {
        return;
    }

    // 将PHP数组转换为JS可识别的JSON
    $config_json = wp_json_encode($wxs_watermark_config);

    // 输出为全局变量（供前端JS直接访问）
    echo "<script type='text/javascript'>";
    echo "window.wxsWatermarkConfig = {$config_json};";
    echo "</script>";
}
// 在页面头部输出配置（确保在JS加载前定义）
add_action('wp_head', 'wxs_output_watermark_config', 10);



// 变体选择器定义
define('VARIATION_SELECTOR_START', 0xfe00);
define('VARIATION_SELECTOR_END', 0xfe0f);
define('VARIATION_SELECTOR_SUPPLEMENT_START', 0xe0100);
define('VARIATION_SELECTOR_SUPPLEMENT_END', 0xe01ef);

/**
 * 字节转换为变体选择器字符
 */
function wxs_toVariationSelector($byte) {
    if ($byte >= 0 && $byte < 16) {
        return mb_chr(VARIATION_SELECTOR_START + $byte, 'UTF-8');
    } elseif ($byte >= 16 && $byte < 256) {
        return mb_chr(VARIATION_SELECTOR_SUPPLEMENT_START + ($byte - 16), 'UTF-8');
    }
    return null;
}

/**
 * 生成水印内容
 */
function wxs_generate_watermark_raw() {
    global $wxs_watermark_config;
    $config = $wxs_watermark_config['watermark_content'];
    $parts = [];
    
    if ($config['include_ip']) {
        $parts[] = "IP:" . wxs_get_client_ip();
    }
    
    if ($config['include_user'] && function_exists('wp_get_current_user')) {
        $user = wp_get_current_user();
        $parts[] = "USER:" . ($user->exists() ? $user->ID : 'guest');
    }
    
    if ($config['include_time']) {
        $parts[] = "TIME:" . date('Y-m-d H:i:s');
    }
    
    if ($config['include_custom'] && !empty($config['custom_text'])) {
        $parts[] = $config['custom_text'];
    }
    
    return implode('|', $parts);
}

/**
 * 生成盲水印（变体选择器序列）
 */
function wxs_generate_watermark_selector() {
    $raw = wxs_generate_watermark_raw();
    $bytes = [];
    $length = strlen($raw);
    
    for ($i = 0; $i < $length; $i++) {
        $bytes[] = ord($raw[$i]);
    }
    
    $selector_str = '';
    foreach ($bytes as $byte) {
        $selector = wxs_toVariationSelector($byte);
        if ($selector !== null) {
            $selector_str .= $selector;
        }
    }
    
    return $selector_str;
}

/**
 * 计算随机插入次数
 */
function wxs_calc_random_count($text_length, $config) {
    if ($config['random']['count_type'] == 1) {
        return max(1, $config['random']['custom_count']);
    } else {
        return max(1, (int)($text_length / $config['random']['word_based_ratio']));
    }
}

/**
 * 处理单个段落文本
 */
function wxs_process_paragraph($text, $watermark, $config) {
    $text_length = mb_strlen($text, 'UTF-8');
    
    if ($text_length < $config['min_paragraph_length']) {
        return $text;
    }
    
    switch ($config['insert_method']) {
        case 1: // 段落末尾插入
            return $text . $watermark;
            
        case 2: // 随机位置插入
            $insert_count = wxs_calc_random_count($text_length, $config);
            $positions = [];
            
            for ($i = 0; $i < $insert_count; $i++) {
                do {
                    $pos = rand(1, $text_length - 1);
                } while (in_array($pos, $positions));
                $positions[] = $pos;
            }
            sort($positions);
            
            $result = '';
            $last_pos = 0;
            foreach ($positions as $pos) {
                $result .= mb_substr($text, $last_pos, $pos - $last_pos, 'UTF-8');
                $result .= $watermark;
                $last_pos = $pos;
            }
            $result .= mb_substr($text, $last_pos, null, 'UTF-8');
            return $result;
            
        case 3: // 固定字数插入
            $interval = $config['fixed']['interval'];
            $result = '';
            
            for ($i = 0; $i < $text_length; $i++) {
                $result .= mb_substr($text, $i, 1, 'UTF-8');
                if (($i + 1) % $interval === 0 && $i < $text_length - 1) {
                    $result .= $watermark;
                }
            }
            return $result;
            
        default:
            return $text;
    }
}

/**
 * 处理HTML内容中的所有段落
 */
/**
 * 处理HTML内容中的所有段落
 */
function wxs_process_html_content($content, $config) {
    if ($config['enable'] != 1) {
        return $content;
    }
    
    // 调试模式处理
    $isDebug = $config['debug_mode'] === 1;
    $rawWatermark = wxs_generate_watermark_raw();
    
    // 调试模式下使用可见标记包裹原始水印内容
    $watermark = $isDebug ? 
        "[水印调试模式:{$rawWatermark}]" : 
        wxs_generate_watermark_selector();
    
    if (empty($watermark)) {
        return $content;
    }
    
    // 使用DOMDocument处理HTML
    $dom = new DOMDocument();
    @$dom->loadHTML('<?xml encoding="UTF-8">' . $content, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    $xpath = new DOMXPath($dom);
    $p_nodes = $xpath->query('//p');
    
    foreach ($p_nodes as $p_node) {
        // 处理p标签内的直接文本节点
        foreach ($p_node->childNodes as $child) {
            if ($child->nodeType === XML_TEXT_NODE) {
                $original_text = $child->nodeValue;
                $processed_text = wxs_process_paragraph($original_text, $watermark, $config);
                if ($processed_text !== $original_text) {
                    $child->nodeValue = $processed_text;
                }
            }
        }
    }
    
    $html = '';
    foreach ($dom->childNodes as $node) {
        $html .= $dom->saveHTML($node);
    }
    return $html;
}

/**
 * 主处理函数
 */
function wxs_watermark_main($content) {
    global $wxs_watermark_config;
    // 检查用户是否已登录，未登录则不插入水印
    if (!is_user_logged_in()) {
        return $content;
    }
    // 爬虫 UA 过滤逻辑
    // $user_agent = strtolower($_SERVER['HTTP_USER_AGENT'] ?? '');
    // foreach ($wxs_watermark_config['bot_ua'] as $bot_keyword) {
    //     if (strpos($user_agent, strtolower($bot_keyword)) !== false) {
    //         return $content; // 是爬虫，返回原始内容
    //     }
    // }
    return wxs_process_html_content($content, $wxs_watermark_config);
}
add_filter('the_content', 'wxs_watermark_main', 999);
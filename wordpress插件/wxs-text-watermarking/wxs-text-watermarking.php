<?php
/*
Plugin Name: 文本盲水印
Plugin URI: https://github.com/twsh0305/text_watermarking
Description: 为文章内容添加盲水印，支持多种插入方式和自定义配置
Version: 1.0.0
Author: 天无神话
Author URI: https://wxsnote.cn/
License: MIT
*/

if (!defined('ABSPATH')) exit;

//插件统一版本
function wxs_watermark_plugin_version(){
    return "1.0.0";
}
$version = wxs_watermark_plugin_version();

// 检查mbstring扩展（核心依赖）
if (!extension_loaded('mbstring')) {
    add_action('admin_notices', function() {
        echo '<div class="error"><p>【文本盲水印】插件依赖 mbstring 扩展，请启用该PHP扩展。</p></div>';
    });
    return; // 终止插件加载
}

// 定义插件根目录路径
define('WXS_WATERMARK_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WXS_WATERMARK_PLUGIN_URL', plugin_dir_url(__FILE__));

// 修正配置获取函数（获取单个配置项）
if (!function_exists('wxs_watermark_get_setting')) {
    function wxs_watermark_get_setting($key = '', $default = null) {
        $all_settings = get_option('wxs_watermark_settings', []);
        return isset($all_settings[$key]) ? $all_settings[$key] : $default;
    }
}

// 安全引入必要文件（带存在性检查）
$required_files = [
    '/inc/codestar-framework/codestar-framework.php',
    '/inc/options.php',
    '/inc/encode.php',
    '/inc/functions.php'
];
foreach ($required_files as $file) {
    $full_path = WXS_WATERMARK_PLUGIN_DIR . $file;
    if (file_exists($full_path)) {
        require_once $full_path;
    } else {
        error_log("文本盲水印插件错误：缺失必要文件 - {$full_path}");
    }
}




// 全局配置变量
$wxs_watermark_config = get_option('wxs_watermark_settings', []);

// 初始化CSF设置面板
if (class_exists('CSF')) {
    // 创建设置页面 - 顶部信息
    CSF::createOptions('wxs_watermark_settings', [
        'menu_title'      => '文本水印',
        'menu_slug'       => 'wxs-watermark',
        'menu_type'       => 'menu',
        'menu_icon'       => 'dashicons-shield',
        'menu_position'   => 58,
        'framework_title' => '文本盲水印配置 <small>v'.$version.'</small>',
        'show_bar_menu'   => false,
    ]);

    // 欢迎页面
    CSF::createSection('wxs_watermark_settings', [
        'id'    => 'wxs_watermark_welcome',
        'title' => '欢迎使用',
        'icon'  => 'fa fa-home',
        'fields' => [
            [
                'type'  => 'content',
                'content' => '
                <div class="wxs-welcome-panel">
                    <h3>文本盲水印插件</h3>
                    <p>感谢使用文本盲水印插件，该插件可以在文章内容中嵌入不可见的盲水印，帮助您保护原创内容。</p>
                    <div class="wxs-features">
                        <div class="feature-box">
                            <h4>多种插入方式</h4>
                            <p>支持段落末尾、随机位置和固定字数三种水印插入方式</p>
                        </div>
                        <div class="feature-box">
                            <h4>自定义水印内容</h4>
                            <p>可包含访问者IP、用户ID、时间戳和自定义文本</p>
                        </div>
                        <div class="feature-box">
                            <h4>爬虫过滤</h4>
                            <p>可设置不向搜索引擎爬虫插入水印</p>
                        </div>
                    </div>
                    <p>请通过左侧选项卡配置插件功能。在调试模式下，水印将以可见文本形式显示，便于测试。</p>
                </div>
                <div>
                    <p>插件作者：天无神话</p>
                    <p>天无神话制作，转载请注明，谢谢合作。</p>
                    <p style="color:red">禁止移除或修改作者信息</p>
                    <p>后台框架：Codestarframework 加密技术：Github emoji-encoder</p>
                    <p>插件开源地址：<a href="https://github.com/twsh0305/text_watermarking" target="_blank">https://github.com/twsh0305/text_watermarking</a></p>
                </div>
                <style>
                    .wxs-welcome-panel { padding: 20px; background: #fff; border-radius: 4px; }
                    .wxs-features { display: flex; flex-wrap: wrap; gap: 20px; margin: 20px 0; }
                    .feature-box { flex: 1; min-width: 250px; padding: 15px; background: #f9f9f9; border-radius: 4px; }
                </style>
                ',
            ],
        ]
    ]);

    // 基础设置面板
    CSF::createSection('wxs_watermark_settings', [
        'title'  => '基础设置',
        'icon'   => 'fa fa-cog',
        'fields' => [
            [
                'type'    => 'heading',
                'content' => '基础设置',
            ],
            [
                'id'      => 'enable',
                'type'    => 'switcher',
                'title'   => '启用盲水印',
                'label'   => '开启后将在文章内容中插入盲水印',
                'default' => 1,
            ],
            [
                'id'      => 'min_paragraph_length',
                'type'    => 'number',
                'title'   => '最小段落字数',
                'desc'    => '少于此字数的段落不插入水印（建议15-30）',
                'default' => 20,
                'min'     => 1,
            ],
            [
                'id'      => 'insert_method',
                'type'    => 'select',
                'title'   => '插入方式',
                'options' => [
                    1 => '段落末尾插入',
                    2 => '随机位置插入',
                    3 => '固定字数插入',
                ],
                'default' => 2,
                'desc'    => '选择水印在文章中的插入方式',
            ],
            
            // 随机位置插入设置（仅当选择随机位置时显示）
            [
                'type'    => 'heading',
                'content' => '随机位置插入设置',
                'dependency' => ['insert_method', '==', 2],
            ],
            [
                'id'        => 'random_count_type',
                'type'      => 'select',
                'title'     => '插入次数模式',
                'options'   => [
                    1 => '自定义次数',
                    2 => '按字数自动计算',
                ],
                'default'   => 2,
                'dependency' => ['insert_method', '==', 2],
            ],
            [
                'id'        => 'random_custom_count',
                'type'      => 'number',
                'title'     => '自定义插入次数',
                'desc'      => '每段固定插入的水印次数',
                'default'   => 1,
                'min'       => 1,
                'dependency' => [
                    ['insert_method', '==', 2],
                    ['random_count_type', '==', 1]
                ],
            ],
            [
                'id'        => 'random_word_ratio',
                'type'      => 'number',
                'title'     => '字数比例',
                'desc'      => '每多少字增加1次插入（例：400=每400字插入1次）',
                'default'   => 400,
                'min'       => 50,
                // 修复依赖条件格式
                'dependency' => [
                    ['insert_method', '==', 2],
                    ['random_count_type', '==', 2]
                ],
            ],
            
            // 固定位置插入设置（仅当选择固定字数时显示）
            [
                'type'    => 'heading',
                'content' => '固定字数插入设置',
                'dependency' => ['insert_method', '==', 3],
            ],
            [
                'id'      => 'fixed_interval',
                'type'    => 'number',
                'title'   => '插入间隔',
                'desc'    => '每多少字插入1次水印',
                'default' => 20,
                'min'     => 5,
                'dependency' => ['insert_method', '==', 3],
            ],
            
            [
                'id'      => 'debug_mode',
                'type'    => 'switcher',
                'title'   => '调试模式',
                'label'   => '启用后水印将以可见文本形式显示',
                'default' => 0,
                'desc'    => '用于测试水印效果，正式环境建议关闭',
            ],
        ]
    ]);

    // 水印内容设置面板
    CSF::createSection('wxs_watermark_settings', [
        'title'  => '水印内容设置',
        'icon'   => 'fa fa-file-text',
        'fields' => [
            [
                'id'      => 'include_ip',
                'type'    => 'switcher',
                'title'   => '包含访问者IP',
                'default' => 1,
            ],
            [
                'id'      => 'include_user',
                'type'    => 'switcher',
                'title'   => '包含用户ID',
                'label'   => '登录用户显示ID，游客显示"guest"',
                'default' => 1,
            ],
            [
                'id'      => 'include_time',
                'type'    => 'switcher',
                'title'   => '包含时间戳',
                'label'   => '水印生成时的时间（YYYY-MM-DD HH:MM:SS）',
                'default' => 1,
            ],
            [
                'id'      => 'include_custom',
                'type'    => 'switcher',
                'title'   => '包含自定义文本',
                'default' => 1,
            ],
            [
                'id'        => 'custom_text',
                'type'      => 'text',
                'title'     => '自定义文本内容',
                'desc'      => '建议包含版权信息（如"XX版权所有"）',
                'default'   => '王先生笔记 版权所有',
                'dependency' => ['include_custom', '==', 1],
            ],
        ]
    ]);

    // 爬虫过滤设置面板
    CSF::createSection('wxs_watermark_settings', [
        'title'  => '爬虫过滤设置',
        'icon'   => 'fa fa-bug',
        'fields' => [
            [
                'id'      => 'bot_ua',
                'type'    => 'textarea',
                'title'   => '爬虫UA列表',
                'desc'    => '每行一个爬虫标识，匹配时不插入水印',
                'default' => "googlebot\nbingbot\nbaiduspider\nsogou web spider\n360spider\nyisouspider\nbytespider\nduckduckbot\nyandexbot\nyahoo",
            ],
        ]
    ]);
}

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
 * 获取客户端IP
 */
function wxs_get_client_ip() {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip_list = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        $ip = trim($ip_list[0]); // 取第一个有效IP
    } elseif (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = trim($_SERVER['HTTP_CLIENT_IP']);
    }
    return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : 'unknown';
}

/**
 * 生成水印内容
 */
function wxs_generate_watermark_raw() {
    global $wxs_watermark_config;
    $parts = [];
    
    if (!empty($wxs_watermark_config['include_ip'])) {
        $parts[] = "IP:" . wxs_get_client_ip();
    }
    
    if (!empty($wxs_watermark_config['include_user']) && function_exists('wp_get_current_user')) {
        $user = wp_get_current_user();
        $parts[] = "USER:" . ($user->exists() ? $user->ID : 'guest');
    }
    
    if (!empty($wxs_watermark_config['include_time'])) {
        $parts[] = "TIME:" . date('Y-m-d H:i:s');
    }
    
    if (!empty($wxs_watermark_config['include_custom']) && !empty($wxs_watermark_config['custom_text'])) {
        $parts[] = sanitize_text_field($wxs_watermark_config['custom_text']);
    }
    
    return implode('|', $parts);
}

/**
 * 生成盲水印（变体选择器序列）
 */
function wxs_generate_watermark_selector() {
    $raw = wxs_generate_watermark_raw();
    if (empty($raw)) return '';
    
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
function wxs_calc_random_count($text_length) {
    global $wxs_watermark_config;
    $count_type = isset($wxs_watermark_config['random_count_type']) ? $wxs_watermark_config['random_count_type'] : 2;
    
    if ($count_type == 1) {
        $custom_count = isset($wxs_watermark_config['random_custom_count']) ? $wxs_watermark_config['random_custom_count'] : 1;
        return max(1, (int)$custom_count);
    } else {
        $ratio = isset($wxs_watermark_config['random_word_ratio']) ? $wxs_watermark_config['random_word_ratio'] : 400;
        return max(1, (int)($text_length / $ratio));
    }
}

/**
 * 处理单个段落文本
 */
function wxs_process_paragraph($text, $watermark) {
    global $wxs_watermark_config;
    $min_length = isset($wxs_watermark_config['min_paragraph_length']) ? $wxs_watermark_config['min_paragraph_length'] : 20;
    $text_length = mb_strlen($text, 'UTF-8');
    
    if ($text_length < $min_length || empty($watermark)) {
        return $text;
    }
    
    $method = isset($wxs_watermark_config['insert_method']) ? $wxs_watermark_config['insert_method'] : 2;
    switch ($method) {
        case 1: // 段落末尾插入
            return $text . $watermark;
            
        case 2: // 随机位置插入
            $insert_count = wxs_calc_random_count($text_length);
            $positions = [];
            
            // 避免插入次数超过文本长度
            $insert_count = min($insert_count, $text_length - 1);
            
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
            $interval = isset($wxs_watermark_config['fixed_interval']) ? $wxs_watermark_config['fixed_interval'] : 20;
            $interval = max(5, (int)$interval); // 确保间隔不小于5
            
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
function wxs_process_html_content($content) {
    global $wxs_watermark_config;
    
    // 检查是否启用
    if (empty($wxs_watermark_config['enable'])) {
        return $content;
    }
    
    // 调试模式处理
    $isDebug = !empty($wxs_watermark_config['debug_mode']);
    $rawWatermark = wxs_generate_watermark_raw();
    $watermark = $isDebug ? "[水印调试模式:{$rawWatermark}]" : wxs_generate_watermark_selector();
    
    if (empty($watermark)) {
        return $content;
    }
    
    // 处理HTML编码问题
    $content = mb_convert_encoding($content, 'HTML-ENTITIES', 'UTF-8');
    
    // 使用DOMDocument处理HTML
    $dom = new DOMDocument();
    @$dom->loadHTML('<?xml encoding="UTF-8">' . $content, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    $xpath = new DOMXPath($dom);
    $p_nodes = $xpath->query('//p');
    
    if ($p_nodes->length > 0) {
        foreach ($p_nodes as $p_node) {
            foreach ($p_node->childNodes as $child) {
                if ($child->nodeType === XML_TEXT_NODE) {
                    $original_text = $child->nodeValue;
                    $processed_text = wxs_process_paragraph($original_text, $watermark);
                    if ($processed_text !== $original_text) {
                        $child->nodeValue = $processed_text;
                    }
                }
            }
        }
    }
    
    // 重建HTML内容
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
    
    // 已登录用户不插入水印
    if (is_user_logged_in()) {
        return $content;
    }
    
    // 爬虫过滤优化
    $user_agent = strtolower($_SERVER['HTTP_USER_AGENT'] ?? '');
    $bot_ua_list = [];
    if (!empty($wxs_watermark_config['bot_ua'])) {
        // 过滤空值和空格
        $bot_ua_list = array_filter(array_map('trim', explode("\n", $wxs_watermark_config['bot_ua'])));
    }
    
    foreach ($bot_ua_list as $bot) {
        if (!empty($bot) && strpos($user_agent, $bot) !== false) {
            return $content;
        }
    }
    
    return wxs_process_html_content($content);
}
add_filter('the_content', 'wxs_watermark_main', 999);

// 脚本入队与配置本地化
add_action('wp_enqueue_scripts', function() {
    // 入队JS文件（仅在单篇文章页）
    if (is_single() && !is_user_logged_in()) {
        wp_enqueue_script(
            'wxs-watermark-script',
            WXS_WATERMARK_PLUGIN_URL . 'lib/assets/js/index.js',
            [],
            $version,
            true
        );
        
        // 本地化配置
        wxs_output_watermark_config();
    }
});

/**
 * 输出配置到前端JS
 */
function wxs_output_watermark_config() {
    global $wxs_watermark_config;
    if (empty($wxs_watermark_config) || !is_array($wxs_watermark_config)) {
        return;
    }
    
    // 格式化配置
    $js_config = [
        'enable' => isset($wxs_watermark_config['enable']) ? $wxs_watermark_config['enable'] : 0,
        'min_paragraph_length' => isset($wxs_watermark_config['min_paragraph_length']) ? $wxs_watermark_config['min_paragraph_length'] : 20,
        'insert_method' => isset($wxs_watermark_config['insert_method']) ? $wxs_watermark_config['insert_method'] : 2,
        'random' => [
            'count_type' => isset($wxs_watermark_config['random_count_type']) ? $wxs_watermark_config['random_count_type'] : 2,
            'custom_count' => isset($wxs_watermark_config['random_custom_count']) ? $wxs_watermark_config['random_custom_count'] : 1,
            'word_based_ratio' => isset($wxs_watermark_config['random_word_ratio']) ? $wxs_watermark_config['random_word_ratio'] : 400,
        ],
        'fixed' => [
            'interval' => isset($wxs_watermark_config['fixed_interval']) ? $wxs_watermark_config['fixed_interval'] : 20,
        ],
        'watermark_content' => [
            'include_ip' => isset($wxs_watermark_config['include_ip']) ? $wxs_watermark_config['include_ip'] : 1,
            'include_user' => isset($wxs_watermark_config['include_user']) ? $wxs_watermark_config['include_user'] : 1,
            'include_time' => isset($wxs_watermark_config['include_time']) ? $wxs_watermark_config['include_time'] : 1,
            'include_custom' => isset($wxs_watermark_config['include_custom']) ? $wxs_watermark_config['include_custom'] : 1,
            'custom_text' => isset($wxs_watermark_config['custom_text']) ? $wxs_watermark_config['custom_text'] : '王先生笔记 版权所有',
        ],
        'bot_ua' => isset($wxs_watermark_config['bot_ua']) ? explode("\n", $wxs_watermark_config['bot_ua']) : [],
        'debug_mode' => isset($wxs_watermark_config['debug_mode']) ? $wxs_watermark_config['debug_mode'] : 0,
    ];
    
    wp_localize_script('wxs-watermark-script', 'wxsWatermarkConfig', $js_config);
}

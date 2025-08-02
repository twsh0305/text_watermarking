<?php
/*
Plugin Name: 文本盲水印
Plugin URI: https://github.com/twsh0305/text_watermarking
Description: 为文章内容添加盲水印，支持多种插入方式和自定义配置
Version: 1.0.3
Author: 天无神话
Author URI: https://wxsnote.cn/
License: MIT
*/

if (!defined('ABSPATH')) exit;

// 插件统一版本
function wxs_watermark_plugin_version(){
    return "1.0.3";
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

// 修正配置获取函数（获取单个个配置项）
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
        error_log("文本盲水印插件错误：缺失失必要文件 - {$full_path}");
    }
}

// 全局配置变量
$wxs_watermark_config = get_option('wxs_watermark_settings', []);

// 添加输出输出JS状态变量的动作
add_action('wp_head', 'wxs_watermark_output_js_vars');
function wxs_watermark_output_js_vars() {
    // 获取用户登录状态
    $is_user_logged_in = is_user_logged_in() ? 'true' : 'false';
    
    // 获取取当前用户ID
    $current_user_id = 'false';
    if (is_user_logged_in()) {
        $user = wp_get_current_user();
        $current_user_id = $user->ID ? (string)$user->ID : 'false';
    }
    
    // 检查是否为文章页面
    $is_article_page = is_single() ? 'true' : 'false';
    
    // 输出变量到页面
    echo "<script type='text/javascript'>\n";
    echo "window.wxs_isUserLoggedIn = {$is_user_logged_in};\n";
    echo "window.wxs_current_user_id = {$current_user_id};\n";
    echo "window.wxs_isArticlePage = {$is_article_page};\n";
    echo "</script>\n";
}

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
        'footer_text'     => '文本盲水印插件-<a href="https://wxsnote.cn" target="_blank">王先生笔记</a> V'.$version,
        'show_bar_menu'   => false,
        'theme'           => 'light',
    ]);

    // 欢迎页面
    CSF::createSection('wxs_watermark_settings', [
        'id'    => 'wxs_watermark_welcome',
        'title' => '欢迎使用',
        'icon'  => 'fa fa-home',
        'fields' => [
            [
                'type'  => 'submessage',
                'style'   => 'warning',
                'content' => '
                <div class="wxs-welcome-panel">
                <h3 style="color:#fd4c73;"><i class="fa fa-heart fa-fw"></i> 感谢您使用文本盲水印插件</h3>
                    
                    <p>插件功能：该插件可以在文章内容中嵌入不可见的盲水印，帮助您保护原创内容。</p>
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
                    <p>插件作者：天无神话</p>
                    <p>作者博客：<a href="https://wxsnote.cn/" target="_blank">王先生笔记</a></p>
                    <p>原理介绍：<a href="https://wxsnote.cn/6395.html" target="_blank">https://wxsnote.cn/6395.html</a></p>
                    <p>开源地址：<a href="https://github.com/twsh0305/text_watermarking" target="_blank">https://github.com/twsh0305/text_watermarking</a></p>
                    <p>QQ群：<a href="https://jq.qq.com/?_wv=1027&k=eiGEOg3i" target="_blank">399019539</a></p>
                    <p>天无神话制作，转载请注明开源地址，谢谢合作。</p>
                    <p style="color:red">开源协议主要要求：禁止移除或修改作者信息</p>
                    <p>后台框架：<a href="https://github.com/Codestar/codestar-framework" target="_blank">Codestar Framework</a> 加密方案：<a href="https://github.com/paulgb/emoji-encoder" target="_blank">Emoji Encoder</a></p>
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
                'content' => '基本设置',
            ],
            [
                'id'      => 'enable',
                'type'    => 'switcher',
                'title'   => '启用盲水印',
                'label'   => '开启后将在文章内容中插入盲水印',
                'default' => 0, // 默认关闭
            ],
            [
                'id'      => 'run_mode',
                'type'    => 'select',
                'title'   => '运行模式',
                'options' => [
                    'dynamic' => '动态（纯PHP）',
                    'static'  => '静态（纯JS）',
                    'hybrid'  => '动静混合',
                ],
                'default' => 'hybrid',
                'desc'    => '动态：纯PHP处理，不管登录状态<br>静态：纯JS处理，不管管登录状态<br>动静混合：登录用户用PHP，未登录用户用JS（适合有缓存的网站）',
                'dependency' => ['enable', '==', 1],
            ],
            [
                'id'      => 'min_paragraph_length',
                'type'    => 'number',
                'title'   => '最小段落字数',
                'desc'    => '少于此字数的段落不插入水印（建议15-30）',
                'default' => 20,
                'min'     => 1,
                'dependency' => ['enable', '==', 1],
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
                'dependency' => ['enable', '==', 1],
            ],
            
            // 随机位置插入设置（仅当选择随机位置时显示）
            [
                'type'    => 'heading',
                'content' => '随机位置插入设置',
                'dependency' => [
                    ['enable', '==', 1],
                    ['insert_method', '==', 2]
                ],
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
                'dependency' => [
                    ['enable', '==', 1],
                    ['insert_method', '==', 2]
                ],
            ],
            [
                'id'        => 'random_custom_count',
                'type'      => 'number',
                'title'     => '自定义插入次数',
                'desc'      => '每段固定插入的水印次数',
                'default'   => 1,
                'min'       => 1,
                'dependency' => [
                    ['enable', '==', 1],
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
                'dependency' => [
                    ['enable', '==', 1],
                    ['insert_method', '==', 2],
                    ['random_count_type', '==', 2]
                ],
            ],
            
            // 固定位置插入设置（仅当选择固定字数时显示）
            [
                'type'    => 'heading',
                'content' => '固定字数插入设置',
                'dependency' => [
                    ['enable', '==', 1],
                    ['insert_method', '==', 3]
                ],
            ],
            [
                'id'      => 'fixed_interval',
                'type'    => 'number',
                'title'   => '插入间隔',
                'desc'    => '每多少字插入1次水印',
                'default' => 20,
                'min'     => 5,
                'dependency' => [
                    ['enable', '==', 1],
                    ['insert_method', '==', 3]
                ],
            ],
            
            [
                'id'      => 'debug_mode',
                'type'    => 'switcher',
                'title'   => '调试模式',
                'label'   => '启用后水印将以可见文本形式显示（[水印调试:...]）',
                'default' => 0,
                'desc'    => '用于测试水印效果，正式环境建议关闭',
                'dependency' => ['enable', '==', 1],
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
                'label'   => '访客的IP地址，用户溯源定位',
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
                'dependency' => [
                    ['include_custom', '==', 1]
                ],
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
                'desc'    => '每行一个爬虫标识，匹配时不插入水印，清空时不匹配，用于防止搜索引擎抓取错误，建议配合WAF使用，拦截假蜘蛛。',
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
    if (!is_int($byte) || $byte < 0 || $byte > 255) {
        return null; // 无效字节
    }
    
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
    
    $raw = implode('|', $parts);
    return $raw;
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
    $watermark = $isDebug ? "[水印调试PHP模式:{$rawWatermark}]" : wxs_generate_watermark_selector();
    
    if (empty($watermark)) {
        return $content;
    }
    
    // 处理HTML编码问题
    $content = mb_convert_encoding($content, 'HTML-ENTITIES', 'UTF-8');
    
    // 使用DOMDocument处理HTML
    $dom = new DOMDocument();
    libxml_use_internal_errors(true); // 禁用libxml错误输出
    $dom->loadHTML('<?xml encoding="UTF-8">' . $content, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    libxml_clear_errors(); // 清除错误
    
    $xpath = new DOMXPath($dom);
    // 处理<p>标签
    $nodes = $xpath->query('//p');
    
    if ($nodes->length > 0) {
        foreach ($nodes as $node) {
            foreach ($node->childNodes as $child) {
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
 * 主处理函数 - 根据运行模式决定处理方式
 */
function wxs_watermark_main($content) {
    global $wxs_watermark_config;
    
    // 检查是否启用
    if (empty($wxs_watermark_config['enable'])) {
        return $content;
    }
    
    // 获取运行模式
    $run_mode = isset($wxs_watermark_config['run_mode']) ? $wxs_watermark_config['run_mode'] : 'hybrid';
    
    // 爬虫过滤
    $user_agent = strtolower($_SERVER['HTTP_USER_AGENT'] ?? '');
    $bot_ua_list = [];
    if (!empty($wxs_watermark_config['bot_ua'])) {
        // 过滤空值和空格
        $bot_ua_list = array_filter(array_map('trim', explode("\n", $wxs_watermark_config['bot_ua'])));
    }
    
    $is_bot = false;
    foreach ($bot_ua_list as $bot) {
        if (!empty($bot) && strpos($user_agent, $bot) !== false) {
            $is_bot = true;
            break;
        }
    }
    
    // 如果是爬虫，不添加水印
    if ($is_bot) {
        return $content;
    }
    
    // 根据运行模式处理
    switch ($run_mode) {
        case 'dynamic':
            // 动态模式：纯PHP处理，不管登录状态
            return wxs_process_html_content($content);
            break;
            
        case 'static':
            // 静态模式：纯JS处理，返回原始内容，由JS处理
            return $content;
            break;
            
        case 'hybrid':
            // 混合模式：登录用户用PHP，未登录用户用JS
            // 强化判断：明确检查登录状态并记录日志
            $is_logged_in = is_user_logged_in();
            if (!empty($wxs_watermark_config['debug_mode'])) {
                error_log("混合模式处理 - 用户登录状态: " . ($is_logged_in ? "已登录(PHP处理)" : "未登录(JS处理)"));
            }
            return $is_logged_in ? wxs_process_html_content($content) : $content;
            break;
            
        default:
            // 未知模式默认使用混合模式逻辑
            $is_logged_in = is_user_logged_in();
            return $is_logged_in ? wxs_process_html_content($content) : $content;
            break;
    }
}
add_filter('the_content', 'wxs_watermark_main', 999);

/**
 * 脚本入队与配置本地化 - 根据运行模式决定是否加载JS
 */
add_action('wp_enqueue_scripts', function() {
    global $wxs_watermark_config, $version;
    
    // 检查是否启用
    if (empty($wxs_watermark_config['enable'])) {
        return;
    }
    
    // 获取运行模式
    $run_mode = isset($wxs_watermark_config['run_mode']) ? $wxs_watermark_config['run_mode'] : 'hybrid';
    
    // 爬虫检测
    $user_agent = strtolower($_SERVER['HTTP_USER_AGENT'] ?? '');
    $bot_ua_list = [];
    if (!empty($wxs_watermark_config['bot_ua'])) {
        $bot_ua_list = array_filter(array_map('trim', explode("\n", $wxs_watermark_config['bot_ua'])));
    }
    
    $is_bot = false;
    foreach ($bot_ua_list as $bot) {
        if (!empty($bot) && strpos($user_agent, $bot) !== false) {
            $is_bot = true;
            break;
        }
    }
    
    // 如果是爬虫，不加载JS
    if ($is_bot) {
        return;
    }
    
    // 纯JS模式下强制加载JS，无论登录状态
    $load_js = false;
    if ($run_mode === 'static') {
        $load_js = true;
        // 调试模式下记录加载信息
        if (!empty($wxs_watermark_config['debug_mode'])) {
            error_log('纯JS模式已启用，加载水印脚本');
        }
    } elseif ($run_mode === 'dynamic') {
        $load_js = false;
    } else { // hybrid
        $load_js = !is_user_logged_in();
    }
    
    // 入队JS文件（单篇文章页）
    if ($load_js && is_single()) {
        wp_enqueue_script(
            'wxs-watermark-script',
            WXS_WATERMARK_PLUGIN_URL . 'lib/assets/js/index.min.js',
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
    
    // 强制在调试模式下输出详细日志
    $is_debug = !empty($wxs_watermark_config['debug_mode']);
    if ($is_debug) {
        error_log('水印调试模式已启用 - 配置信息: ' . print_r($wxs_watermark_config, true));
    }
    
    // 生成水印内容供JS使用
    $watermark_raw = wxs_generate_watermark_raw();
    
    // 格式化配置，确保debug_mode正确传递
    $js_config = [
        'enable' => isset($wxs_watermark_config['enable']) ? $wxs_watermark_config['enable'] : 0,
        'ip_endpoint' => WXS_WATERMARK_PLUGIN_URL . 'fuckip.php',
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
        'debug_mode' => $is_debug ? 1 : 0, // 确保是数字类型
        'run_mode' => isset($wxs_watermark_config['run_mode']) ? $wxs_watermark_config['run_mode'] : 'hybrid', // 传递运行模式
    ];
    
    wp_localize_script('wxs-watermark-script', 'wxsWatermarkConfig', $js_config);
}
    

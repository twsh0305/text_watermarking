<?php
/*
Plugin Name: Blind text watermarking
Plugin URI: https://github.com/twsh0305/text_watermarking
Description: Add blind watermark to article content, support multiple insertion methods and custom configurations, filter UA whitelist
Version: 1.0.8
Author: 天无神话
Author URI: https://wxsnote.cn/
Text Domain: wxs-text-watermarking
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html
Copyright (C) 2025 天无神话
This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; either version 3 of the License, or (at your option) any later version.
*/

if (!defined("ABSPATH")) {
    exit();
}

// Plugin unified version // 插件统一版本
function wxs_watermark_plugin_version()
{
    return "1.0.8";
}
$version = wxs_watermark_plugin_version();

// Check mbstring PHP extension // 检查mbstring的PHP扩展
if (!extension_loaded("mbstring")) {
    add_action("admin_notices", function () {
        echo '<div class="error"><p>【Text Blind Watermarking】Plugin depends on mbstring PHP extension, please enable or install this PHP extension.</p></div>';
    });
    return;
}

// Define plugin root directory path // 定义插件根目录路径
define("WXS_WATERMARK_PLUGIN_DIR", plugin_dir_path(__FILE__));
define("WXS_WATERMARK_PLUGIN_URL", plugin_dir_url(__FILE__));

// Configuration retrieval // 配置获取
if (!function_exists("wxs_watermark_get_setting")) {
    function wxs_watermark_get_setting($key = "", $default = null)
    {
        $all_settings = get_option("wxs_watermark_init_csf_options", []);
        return isset($all_settings[$key]) ? $all_settings[$key] : $default;
    }
}

function wxs_watermark_load_textdomain() {
    $domain = 'wxs-text-watermarking';
    $locale = apply_filters('plugin_locale', determine_locale(), $domain);
    
    // First try to load from plugin directory // 首先尝试从插件目录加载
    $mofile = WXS_WATERMARK_PLUGIN_DIR . 'languages/' . $domain . '-' . $locale . '.mo';
    
    if (file_exists($mofile)) {
        load_textdomain($domain, $mofile);
    } else {
        // Fallback to WordPress language directory // 回退到WordPress语言目录
        load_plugin_textdomain(
            $domain,
            false,
            dirname(plugin_basename(__FILE__)) . '/languages'
        );
    }
}

add_action('init', 'wxs_watermark_load_textdomain');

// Check if current theme is zibll theme or its child theme // 判断当前主题是否是zibll主题或其子主题
function is_zibll_themes()
{
    // Get current theme object // 获取当前主题对象
    $current_theme = wp_get_theme();

    // Check if current theme is zibll main theme // 检测当前主题是否是zibll主主题
    if ($current_theme->get_stylesheet() === "zibll") {
        return true;
    }

    // Check if current theme is zibll child theme (parent theme is zibll) // 检测当前主题是否是zibll的子主题（父主题为zibll）
    if ($current_theme->get("Template") === "zibll") {
        return true;
    }

    // Neither // 都不是
    return false;
}

/**
 * Load plugin admin styles // 加载插件后台样式
 */
function wxs_watermark_enqueue_admin_styles()
{
    // Get current admin screen object // 获取当前后台屏幕对象
    $current_screen = get_current_screen();
    $prefix = "wxs_watermark_init_csf_options";

    // Load styles only on plugin settings page // 仅在插件设置页面加载样式
    if (
        isset($current_screen->id) &&
        $current_screen->id === "toplevel_page_" . $prefix
    ) {
        // Load Font Awesome // 加载Font Awesome
        wp_enqueue_style(
            "font-awesome-7",
            WXS_WATERMARK_PLUGIN_URL . "lib/assets/css/all.min.css?ver=",
            [],
            "7.0.0",
            "all"
        );

        // Original plugin styles // 原有插件样式
        wp_enqueue_style(
            "wxs-watermark-admin-style",
            WXS_WATERMARK_PLUGIN_URL . "lib/assets/css/style.min.css?ver=",
            [],
            wxs_watermark_plugin_version(),
            "all"
        );
    }
}

// Initialize all functions that need translation // 初始化所有需要翻译的功能
function wxs_watermark_init_translated_functions() {
    // Global configuration variable // 全局配置变量
    global $wxs_watermark_config;
    $wxs_watermark_config = get_option("wxs_watermark_init_csf_options", []);
    
    // Variable to record CSF initialization status // 记录CSF初始化状态的变量
    $csf_initialized = false;

    // Initialize CSF settings panel // 初始化CSF设置面板
    if (class_exists("CSF")) {
        $csf_initialized = wxs_watermark_init_csf_settings();
    } else {
        $csf_initialized = false;
    }

    // Add fallback menu registration to ensure plugin entry is displayed even when CSF doesn't work properly // 添加备用菜单注册方式，确保在CSF无法正常工作时仍能显示插件入口
    if (!$csf_initialized) {
        if (!is_zibll_themes()) {
            add_action("admin_menu", "wxs_watermark_add_fallback_menu");
        }
    }
    
    // Hook to admin style loading hook // 挂钩到后台样式加载钩子
    if (!is_zibll_themes()) {
        add_action(
            "admin_enqueue_scripts",
            "wxs_watermark_enqueue_admin_styles",
            500
        );
    }
}
add_action('init', 'wxs_watermark_init_translated_functions');

if (is_zibll_themes()) {
    // Use Zibll function to mount // 使用子比函数挂载
    require_once WXS_WATERMARK_PLUGIN_DIR . "/lib/wxs-settings.php";
    add_action("zib_require_end", "wxs_watermark_init_csf_settings");
} else {
    // Non-Zibll introduce necessary files // 非子比引入必要文件
    $required_files = [
        "/lib/codestar-framework/codestar-framework.php",
        "/lib/wxs-settings.php",
    ];

    // Check if Codestar Framework already exists // 检查Codestar Framework是否已存在
    $csf_exists = class_exists("CSF");
    foreach ($required_files as $file) {
        $full_path = WXS_WATERMARK_PLUGIN_DIR . $file;

        // Skip loading if it's Codestar framework file and already exists // 如果是Codestar框架文件且已存在，则跳过加载
        if (
            $file === "/lib/codestar-framework/codestar-framework.php" &&
            $csf_exists
        ) {
            continue;
        }

        // Load other files // 加载其他文件
        if (file_exists($full_path)) {
            require_once $full_path;
        } else {
            // Use original Chinese before text domain is loaded // 在文本域加载前使用原始中文
            error_log("Text Blind Watermarking Plugin Error: Missing necessary file - {$full_path}");
        }
    }
}

// Add action to output JS state variables // 添加输出JS状态变量的动作
add_action("wp_head", "wxs_watermark_output_js_vars");
function wxs_watermark_output_js_vars()
{
    // Get user login status // 获取用户登录状态
    $is_user_logged_in = is_user_logged_in() ? "true" : "false";

    // Get current user ID // 获取当前用户ID
    $current_user_id = "false";
    if (is_user_logged_in()) {
        $user = wp_get_current_user();
        $current_user_id = $user->ID ? (string) $user->ID : "false";
    }

    // Check if it's an article page // 检查是否为文章页面
    $is_article_page = is_single() ? "true" : "false";

    // Output variables to page // 输出变量到页面
    echo "<script type='text/javascript'>\n";
    echo "window.wxs_isUserLoggedIn = {$is_user_logged_in};\n";
    echo "window.wxs_current_user_id = {$current_user_id};\n";
    echo "window.wxs_isArticlePage = {$is_article_page};\n";
    echo "</script>\n";
}

/**
 * Fallback menu registration function, used when CSF doesn't work properly // 备用菜单注册函数，当CSF无法正常工作时使用
 */
function wxs_watermark_add_fallback_menu()
{
    $prefix = "wxs_watermark_init_csf_options"; // Use new prefix // 使用新前缀
    // Add top-level menu // 添加顶级菜单
    add_menu_page(
        __("Text Blind Watermark Configuration", 'wxs-text-watermarking'),
        __("Text Watermark", 'wxs-text-watermarking'),
        "manage_options",
        $prefix, // Menu identifier uses new prefix // 菜单标识使用新前缀
        "wxs_watermark_fallback_page",
        "dashicons-shield",
        58
    );

    // Add submenu // 添加子菜单
    add_submenu_page(
        $prefix,
        __("Text Blind Watermark Configuration", 'wxs-text-watermarking'),
        __("Settings", 'wxs-text-watermarking'),
        "manage_options",
        $prefix,
        "wxs_watermark_fallback_page"
    );
}

/**
 * Fallback page content, displayed when CSF doesn't work properly // 备用页面内容，当CSF无法正常工作时显示
 */
function wxs_watermark_fallback_page()
{
    if (!current_user_can("manage_options")) {
        wp_die(__("You do not have sufficient permissions to access this page.", 'wxs-text-watermarking'));
    }

    // Check if CSF is loaded // 检查CSF是否加载
    $csf_loaded = class_exists("CSF") ? __("Loaded", 'wxs-text-watermarking') : __("Not Loaded", 'wxs-text-watermarking');

    echo '<div class="wrap">';
    echo "<h1>" . __("Text Blind Watermark Configuration", 'wxs-text-watermarking') . "</h1>";
    echo '<div class="notice notice-warning">';
    echo "<p>" . __("Detected that the configuration panel framework did not load properly, possibly due to missing or corrupted files.", 'wxs-text-watermarking') . "</p>";
    echo "<p>" . __("CSF Framework Status: ", 'wxs-text-watermarking') . $csf_loaded . "</p>";
    echo "<p>" . __("Please check if the ", 'wxs-text-watermarking') . "<code>inc/codestar-framework/</code> " . __("folder exists and is complete.", 'wxs-text-watermarking') . "</p>";
    echo "<p>" . __("If the problem persists, please reinstall the plugin.", 'wxs-text-watermarking') . "</p>";
    echo "</div>";
    echo "</div>";
}

// Variation selector definitions // 变体选择器定义
define("VARIATION_SELECTOR_START", 0xfe00);
define("VARIATION_SELECTOR_END", 0xfe0f);
define("VARIATION_SELECTOR_SUPPLEMENT_START", 0xe0100);
define("VARIATION_SELECTOR_SUPPLEMENT_END", 0xe01ef);

/**
 * Convert byte to variation selector character // 字节转换为变体选择器字符
 */
function wxs_toVariationSelector($byte)
{
    if (!is_int($byte) || $byte < 0 || $byte > 255) {
        return null;
    }

    if ($byte >= 0 && $byte < 16) {
        return mb_chr(VARIATION_SELECTOR_START + $byte, "UTF-8");
    } elseif ($byte >= 16 && $byte < 256) {
        return mb_chr(
            VARIATION_SELECTOR_SUPPLEMENT_START + ($byte - 16),
            "UTF-8"
        );
    }
    return null;
}

/**
 * Get client IP // 获取客户端IP
 */
function wxs_get_client_ip()
{
    $ip = $_SERVER["REMOTE_ADDR"] ?? "unknown";
    if (!empty($_SERVER["HTTP_X_FORWARDED_FOR"])) {
        $ip_list = explode(",", $_SERVER["HTTP_X_FORWARDED_FOR"]);
        $ip = trim($ip_list[0]); // Take first valid IP // 取第一个有效IP
    } elseif (!empty($_SERVER["HTTP_CLIENT_IP"])) {
        $ip = trim($_SERVER["HTTP_CLIENT_IP"]);
    }
    return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : "unknown";
}

/**
 * Generate watermark content // 生成水印内容
 */
function wxs_generate_watermark_raw()
{
    global $wxs_watermark_config;
    $parts = [];

    if (!empty($wxs_watermark_config["include_ip"])) {
        $parts[] = "IP:" . wxs_get_client_ip();
    }

    if (
        !empty($wxs_watermark_config["include_user"]) &&
        function_exists("wp_get_current_user")
    ) {
        $user = wp_get_current_user();
        $parts[] = "USER:" . ($user->exists() ? $user->ID : __("guest", 'wxs-text-watermarking'));
    }

    if (!empty($wxs_watermark_config["include_time"])) {
        // Read WordPress global timezone settings // 读取 WordPress 全局时区设置
        $wp_timezone_string = get_option("timezone_string"); // Priority get complete timezone (e.g., Asia/Shanghai) // 优先获取完整时区（如 Asia/Shanghai）
        $wp_gmt_offset = get_option("gmt_offset"); // Fallback: GMT offset // 备用：GMT偏移量

        // Build WordPress timezone object // 构建 WordPress 时区对象
        if (!empty($wp_timezone_string)) {
            // Has complete timezone identifier (recommended, e.g., Asia/Shanghai) // 有完整时区标识符（推荐，如 Asia/Shanghai）
            $timezone = new DateTimeZone($wp_timezone_string);
        } else {
            // Only GMT offset // 只有GMT偏移量
            $offset_sign = $wp_gmt_offset >= 0 ? "+" : "-";
            $offset_hours = abs((int) $wp_gmt_offset);
            $offset_minutes = abs(($wp_gmt_offset - (int) $wp_gmt_offset) * 60);
            $timezone_string = sprintf(
                "Etc/GMT%s%02d:%02d",
                $offset_sign,
                $offset_hours,
                $offset_minutes
            );
            $timezone = new DateTimeZone($timezone_string);
        }

        // Generate WordPress local time (format: YYYY-MM-DD HH:MM:SS) // 生成 WordPress 当地时间（格式：YYYY-MM-DD HH:MM:SS）
        $wp_local_time = new DateTime("now", $timezone);
        $time_str = $wp_local_time->format("Y-m-d H:i:s");

        // Add to watermark content // 加入水印内容
        $parts[] = "TIME:" . $time_str;
    }

    if (
        !empty($wxs_watermark_config["include_custom"]) &&
        !empty($wxs_watermark_config["custom_text"])
    ) {
        $parts[] = sanitize_text_field($wxs_watermark_config["custom_text"]);
    }

    $raw = implode("|", $parts);
    return $raw;
}

/**
 * Generate blind watermark (variation selector sequence) // 生成盲水印（变体选择器序列）
 */
function wxs_generate_watermark_selector()
{
    $raw = wxs_generate_watermark_raw();
    if (empty($raw)) {
        return "";
    }

    $bytes = [];
    $length = strlen($raw);

    for ($i = 0; $i < $length; $i++) {
        $bytes[] = ord($raw[$i]);
    }

    $selector_str = "";
    foreach ($bytes as $byte) {
        $selector = wxs_toVariationSelector($byte);
        if ($selector !== null) {
            $selector_str .= $selector;
        }
    }

    return $selector_str;
}

/**
 * Calculate random insertion count // 计算随机插入次数
 */
function wxs_calc_random_count($text_length)
{
    global $wxs_watermark_config;
    $count_type = isset($wxs_watermark_config["random_count_type"])
        ? $wxs_watermark_config["random_count_type"]
        : 2;

    if ($count_type == 1) {
        $custom_count = isset($wxs_watermark_config["random_custom_count"])
            ? $wxs_watermark_config["random_custom_count"]
            : 1;
        return max(1, (int) $custom_count);
    } else {
        $ratio = isset($wxs_watermark_config["random_word_ratio"])
            ? $wxs_watermark_config["random_word_ratio"]
            : 400;
        return max(1, (int) ($text_length / $ratio));
    }
}

/**
 * Process single paragraph text // 处理单个段落文本
 */
function wxs_process_paragraph($text, $watermark)
{
    global $wxs_watermark_config;
    $min_length = isset($wxs_watermark_config["min_paragraph_length"])
        ? $wxs_watermark_config["min_paragraph_length"]
        : 20;
    $text_length = mb_strlen($text, "UTF-8");

    if ($text_length < $min_length || empty($watermark)) {
        return $text;
    }

    $method = isset($wxs_watermark_config["insert_method"])
        ? $wxs_watermark_config["insert_method"]
        : 2;
    switch ($method) {
        case 1: // Insert at paragraph end // 段落末尾插入
            return $text . $watermark;

        case 2: // Insert at random positions // 随机位置插入
            $insert_count = wxs_calc_random_count($text_length);
            $positions = [];

            // Avoid insertion count exceeding text length // 避免插入次数超过文本长度
            $insert_count = min($insert_count, $text_length - 1);

            for ($i = 0; $i < $insert_count; $i++) {
                do {
                    $pos = rand(1, $text_length - 1);
                } while (in_array($pos, $positions));
                $positions[] = $pos;
            }
            sort($positions);

            $result = "";
            $last_pos = 0;
            foreach ($positions as $pos) {
                $result .= mb_substr(
                    $text,
                    $last_pos,
                    $pos - $last_pos,
                    "UTF-8"
                );
                $result .= $watermark;
                $last_pos = $pos;
            }
            $result .= mb_substr($text, $last_pos, null, "UTF-8");
            return $result;

        case 3: // Insert at fixed character intervals // 固定字数插入
            $interval = isset($wxs_watermark_config["fixed_interval"])
                ? $wxs_watermark_config["fixed_interval"]
                : 20;
            $interval = max(5, (int) $interval); // Ensure interval is not less than 5 // 确保间隔不小于5

            $result = "";
            for ($i = 0; $i < $text_length; $i++) {
                $result .= mb_substr($text, $i, 1, "UTF-8");
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
 * Get configured HTML tags // 获取配置的HTML标签
 */
function wxs_watermark_get_html_tags()
{
    $tags = wxs_watermark_get_setting("html_tags", "p,li");
    $tags = array_map("trim", explode(",", $tags));
    return array_filter($tags); // Filter empty values // 过滤空值
}

/**
 * Process all configured tags in HTML content // 处理HTML内容中的所有配置标签
 */
function wxs_process_html_content($content)
{
    global $wxs_watermark_config;

    // Check if enabled // 检查是否启用
    if (empty($wxs_watermark_config["enable"])) {
        return $content;
    }

    // Debug mode processing // 调试模式处理
    $isDebug = !empty($wxs_watermark_config["debug_mode"]);
    $rawWatermark = wxs_generate_watermark_raw();
    $watermark = $isDebug
        ? "[" . __("Watermark Debug PHP Mode:", 'wxs-text-watermarking') . "{$rawWatermark}]"
        : wxs_generate_watermark_selector();

    if (empty($watermark)) {
        return $content;
    }

    // Get configured tags // 获取配置的标签
    $tags = wxs_watermark_get_html_tags();
    if (empty($tags)) {
        $tags = ["p", "li"];
    }

    // Handle HTML encoding issues // 处理HTML编码问题
    $content = htmlspecialchars_decode(
        htmlentities($content, ENT_QUOTES, "UTF-8")
    );

    // Use DOMDocument to process HTML // 使用DOMDocument处理HTML
    $dom = new DOMDocument();
    libxml_use_internal_errors(true); // Disable libxml error output // 禁用libxml错误输出
    $dom->loadHTML(
        '<?xml encoding="UTF-8">' . $content,
        LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
    );
    libxml_clear_errors(); // Clear errors // 清除错误

    $xpath = new DOMXPath($dom);

    // Process all configured tags // 处理所有配置的标签
    foreach ($tags as $tag) {
        $nodes = $xpath->query("//{$tag}");

        if ($nodes->length > 0) {
            foreach ($nodes as $node) {
                foreach ($node->childNodes as $child) {
                    if ($child->nodeType === XML_TEXT_NODE) {
                        $original_text = $child->nodeValue;
                        $processed_text = wxs_process_paragraph(
                            $original_text,
                            $watermark
                        );
                        if ($processed_text !== $original_text) {
                            $child->nodeValue = $processed_text;
                        }
                    }
                }
            }
        }
    }

    // Rebuild HTML content // 重建HTML内容
    $html = "";
    foreach ($dom->childNodes as $node) {
        $html .= $dom->saveHTML($node);
    }
    return $html;
}

/**
 * Main processing function - decide processing method based on run mode // 主处理函数 - 根据运行模式决定处理方式
 */
function wxs_watermark_main($content)
{
    global $wxs_watermark_config;

    // Check if enabled // 检查是否启用
    if (empty($wxs_watermark_config["enable"])) {
        return $content;
    }

    // Get run mode // 获取运行模式
    $run_mode = isset($wxs_watermark_config["run_mode"])
        ? $wxs_watermark_config["run_mode"]
        : "hybrid";

    // Crawler filtering // 爬虫过滤
    $user_agent = strtolower($_SERVER["HTTP_USER_AGENT"] ?? "");
    $bot_ua_list = [];
    if (!empty($wxs_watermark_config["bot_ua"])) {
        // Filter empty values and spaces // 过滤空值和空格
        $bot_ua_list = array_filter(
            array_map("trim", explode("\n", $wxs_watermark_config["bot_ua"]))
        );
    }

    $is_bot = false;
    foreach ($bot_ua_list as $bot) {
        if (!empty($bot) && strpos($user_agent, $bot) !== false) {
            $is_bot = true;
            break;
        }
    }

    // If it's a crawler, don't add watermark // 如果是爬虫，不添加水印
    if ($is_bot) {
        return $content;
    }

    // Process based on run mode // 根据运行模式处理
    switch ($run_mode) {
        case "dynamic":
            // Dynamic mode: Pure PHP processing, regardless of login status // 动态模式：纯PHP处理，不管是否为登录状态
            return wxs_process_html_content($content);
            break;

        case "static":
            // Static mode: Pure JS processing, return original content, handled by JS // 静态模式：纯JS处理，返回原始内容，由JS处理
            return $content;
            break;

        case "hybrid":
            // Hybrid mode: PHP for logged-in users, JS for non-logged-in users // 混合模式：登录用户用PHP，未登录用户用JS
            $is_logged_in = is_user_logged_in();
            if (!empty($wxs_watermark_config["debug_mode"])) {
                error_log(
                    __("Hybrid Mode Processing - User Login Status: ", 'wxs-text-watermarking') .
                        ($is_logged_in ? __("Logged in (PHP processing)", 'wxs-text-watermarking') : __("Not logged in (JS processing)", 'wxs-text-watermarking'))
                );
            }
            return $is_logged_in
                ? wxs_process_html_content($content)
                : $content;
            break;

        default:
            // Unknown mode defaults to hybrid mode logic // 未知模式默认使用混合模式逻辑
            $is_logged_in = is_user_logged_in();
            return $is_logged_in
                ? wxs_process_html_content($content)
                : $content;
            break;
    }
}
// Also mount content processing after init // 将内容处理也挂载到init之后
add_action('init', function() {
    add_filter("the_content", "wxs_watermark_main", 999);
});

/**
 * Script enqueue and configuration localization - decide whether to load JS based on run mode // 脚本入队与配置本地化 - 根据运行模式决定是否加载JS
 */
add_action("wp_enqueue_scripts", function () {
    global $wxs_watermark_config, $version;

    // Check if enabled // 检查是否启用
    if (empty($wxs_watermark_config["enable"])) {
        return;
    }

    // Get run mode // 获取运行模式
    $run_mode = isset($wxs_watermark_config["run_mode"])
        ? $wxs_watermark_config["run_mode"]
        : "hybrid";

    // Crawler detection // 爬虫检测
    $user_agent = strtolower($_SERVER["HTTP_USER_AGENT"] ?? "");
    $bot_ua_list = [];
    if (!empty($wxs_watermark_config["bot_ua"])) {
        $bot_ua_list = array_filter(
            array_map("trim", explode("\n", $wxs_watermark_config["bot_ua"]))
        );
    }

    $is_bot = false;
    foreach ($bot_ua_list as $bot) {
        if (!empty($bot) && strpos($user_agent, $bot) !== false) {
            $is_bot = true;
            break;
        }
    }

    // If it's a crawler, don't load JS // 如果是爬虫，不加载JS
    if ($is_bot) {
        return;
    }

    // Force JS loading in pure JS mode, regardless of login status // 纯JS模式下强制加载JS，无论登录状态
    $load_js = false;
    if ($run_mode === "static") {
        $load_js = true;
        // Record loading information in debug mode // 调试模式下记录加载信息
        if (!empty($wxs_watermark_config["debug_mode"])) {
            error_log(__("Pure JS mode enabled, loading watermark script", 'wxs-text-watermarking'));
        }
    } elseif ($run_mode === "dynamic") {
        $load_js = false;
    } else {
        // hybrid
        $load_js = !is_user_logged_in();
    }

    // Enqueue JS file // 入队JS文件
    if ($load_js) {
        wp_enqueue_script(
            "wxs-watermark-script",
            WXS_WATERMARK_PLUGIN_URL . "lib/assets/js/index.min.js",
            [],
            $version,
            true
        );

        // Localize configuration // 本地化配置
        wxs_output_watermark_config();
    }
});

/**
 * Output configuration to frontend JS // 输出配置到前端JS
 */
function wxs_output_watermark_config()
{
    global $wxs_watermark_config;
    if (empty($wxs_watermark_config) || !is_array($wxs_watermark_config)) {
        return;
    }

    // Force detailed log output in debug mode // 强制在调试模式下输出详细日志
    $is_debug = !empty($wxs_watermark_config["debug_mode"]);
    if ($is_debug) {
        error_log(
            __("Watermark Debug Mode Enabled - Configuration Information: ", 'wxs-text-watermarking') .
                print_r($wxs_watermark_config, true)
        );
    }

    // Generate watermark content for JS use // 生成水印内容供JS使用
    $watermark_raw = wxs_generate_watermark_raw();

    // Get configured HTML tags // 获取配置的HTML标签
    $html_tags = wxs_watermark_get_html_tags();
    if (empty($html_tags)) {
        $html_tags = ["p", "li"];
    }

    // Format configuration, ensure debug_mode is correctly passed // 格式化配置，确保debug_mode正确传递
    $js_config = [
        "enable" => isset($wxs_watermark_config["enable"])
            ? $wxs_watermark_config["enable"]
            : 0,
        "ip_endpoint" => WXS_WATERMARK_PLUGIN_URL . "fuckip.php",
        "min_paragraph_length" => isset(
            $wxs_watermark_config["min_paragraph_length"]
        )
            ? $wxs_watermark_config["min_paragraph_length"]
            : 20,
        "insert_method" => isset($wxs_watermark_config["insert_method"])
            ? $wxs_watermark_config["insert_method"]
            : 2,
        "random" => [
            "count_type" => isset($wxs_watermark_config["random_count_type"])
                ? $wxs_watermark_config["random_count_type"]
                : 2,
            "custom_count" => isset(
                $wxs_watermark_config["random_custom_count"]
            )
                ? $wxs_watermark_config["random_custom_count"]
            : 1,
            "word_based_ratio" => isset(
                $wxs_watermark_config["random_word_ratio"]
            )
                ? $wxs_watermark_config["random_word_ratio"]
                : 400,
        ],
        "fixed" => [
            "interval" => isset($wxs_watermark_config["fixed_interval"])
                ? $wxs_watermark_config["fixed_interval"]
                : 20,
        ],
        "watermark_content" => [
            "include_ip" => isset($wxs_watermark_config["include_ip"])
                ? $wxs_watermark_config["include_ip"]
                : 1,
            "include_user" => isset($wxs_watermark_config["include_user"])
                ? $wxs_watermark_config["include_user"]
                : 1,
            "include_time" => isset($wxs_watermark_config["include_time"])
                ? $wxs_watermark_config["include_time"]
                : 1,
            "include_custom" => isset($wxs_watermark_config["include_custom"])
                ? $wxs_watermark_config["include_custom"]
                : 1,
            "custom_text" => isset($wxs_watermark_config["custom_text"])
                ? $wxs_watermark_config["custom_text"]
                : __("Mr. Wang's Notes All Rights Reserved", 'wxs-text-watermarking'),
        ],
        // New configuration items // 新增配置项
        "htmlTags" => $html_tags,
        "jsGlobalEnable" => isset($wxs_watermark_config["js_global_enable"])
            ? $wxs_watermark_config["js_global_enable"]
            : 0,
        "jsClassSelectors" => isset($wxs_watermark_config["js_class_selectors"])
            ? $wxs_watermark_config["js_class_selectors"]
            : "",
        "jsIdSelectors" => isset($wxs_watermark_config["js_id_selectors"])
            ? $wxs_watermark_config["js_id_selectors"]
            : "",
        "global_force_article" => isset(
            $wxs_watermark_config["global_force_article"]
        )
            ? $wxs_watermark_config["global_force_article"]
            : 0,

        "bot_ua" => isset($wxs_watermark_config["bot_ua"])
            ? explode("\n", $wxs_watermark_config["bot_ua"])
            : [],
        "debug_mode" => $is_debug ? 1 : 0, // Ensure it's numeric type // 确保是数字类型
        "run_mode" => isset($wxs_watermark_config["run_mode"])
            ? $wxs_watermark_config["run_mode"]
            : "hybrid", // Pass run mode // 传递运行模式
    ];

    wp_localize_script(
        "wxs-watermark-script",
        "wxsWatermarkConfig",
        $js_config
    );
}

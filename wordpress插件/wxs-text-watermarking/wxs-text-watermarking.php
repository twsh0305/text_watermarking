<?php
/**
 * Plugin Name: Wxs Text Watermarking
 * Plugin URI: https://github.com/WordPress/plugin-check
 * Description: Add blind watermark to article content, support multiple insertion methods and custom configurations, filter UA whitelist
 * Requires at least: 6.3
 * Requires PHP: 7.4
 * Version: 1.0.9
 * Author: 天无神话
 * License: GPLv3 or later
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: wxs-text-watermarking
 * Domain Path: /languages
 */


if (!defined("ABSPATH")) {
    exit();
}

// 插件统一版本
function wxs_watermark_plugin_version()
{
    return "1.0.9";
}
$version = wxs_watermark_plugin_version();

// 检查mbstring的PHP扩展
if (!extension_loaded("mbstring")) {
    add_action("admin_notices", function () {
        echo '<div class="error"><p>' . esc_html__('【Text Blind Watermarking】Plugin depends on mbstring PHP extension, please enable or install this PHP extension.', 'wxs-text-watermarking') . '</p></div>';
    });
    return;
}

// 定义插件根目录路径
define("WXS_WATERMARK_PLUGIN_DIR", plugin_dir_path(__FILE__));
define("WXS_WATERMARK_PLUGIN_URL", plugin_dir_url(__FILE__));

// 配置获取
if (!function_exists("wxs_watermark_get_setting")) {
    function wxs_watermark_get_setting($key = "", $default = null)
    {
        $all_settings = get_option("wxs_watermark_init_csf_options", []);
        return isset($all_settings[$key]) ? $all_settings[$key] : $default;
    }
}

// 判断当前主题是否是zibll主题或其子主题
function is_zibll_themes()
{
// 获取当前主题对象
    $current_theme = wp_get_theme();

// 检测当前主题是否是zibll主主题
    if ($current_theme->get_stylesheet() === "zibll") {
        return true;
    }

// 检测当前主题是否是zibll的子主题（父主题为zibll）
    if ($current_theme->get("Template") === "zibll") {
        return true;
    }

    // Neither // 都不是
    return false;
}

// 加载插件后台样式
function wxs_watermark_enqueue_admin_styles()
{
    // 获取当前后台屏幕对象
    $current_screen = get_current_screen();
    $prefix = "wxs_watermark_init_csf_options";

    // 仅在插件设置页面加载样式
    if (
        isset($current_screen->id) &&
        $current_screen->id === "toplevel_page_" . $prefix
    ) {
        // 加载Font Awesome
        wp_enqueue_style(
            "font-awesome-7",
            WXS_WATERMARK_PLUGIN_URL . "lib/assets/css/all.min.css?ver=",
            [],
            "7.0.0",
            "all"
        );

        // 原有插件样式
        wp_enqueue_style(
            "wxs-watermark-admin-style",
            WXS_WATERMARK_PLUGIN_URL . "lib/assets/css/style.min.css?ver=",
            [],
            wxs_watermark_plugin_version(),
            "all"
        );
    }
}

// 初始化所有需要翻译的功能
function wxs_watermark_init_translated_functions() {
    // 全局配置变量
    global $wxs_watermark_config;
    $wxs_watermark_config = get_option("wxs_watermark_init_csf_options", []);
    
    // 记录CSF初始化状态的变量
    $csf_initialized = false;

    // 初始化CSF设置面板
    if (class_exists("CSF")) {
        $csf_initialized = wxs_watermark_init_csf_settings();
    } else {
        $csf_initialized = false;
    }

    // 添加备用菜单注册方式，确保在CSF无法正常工作时仍能显示插件入口
    if (!$csf_initialized) {
        if (!is_zibll_themes()) {
            add_action("admin_menu", "wxs_watermark_add_fallback_menu");
        }
    }
    
    // 挂钩到后台样式加载钩子
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
    // 使用子比函数挂载
    require_once WXS_WATERMARK_PLUGIN_DIR . "/lib/wxs-settings.php";
    add_action("zib_require_end", "wxs_watermark_init_csf_settings");
} else {
    // 非子比引入必要文件
    $required_files = [
        "/lib/codestar-framework/codestar-framework.php",
        "/lib/wxs-settings.php",
    ];

    // 检查Codestar Framework是否已存在
    $csf_exists = class_exists("CSF");
    foreach ($required_files as $file) {
        $full_path = WXS_WATERMARK_PLUGIN_DIR . $file;
        // 如果是Codestar框架文件且已存在，则跳过加载
        if (
            $file === "/lib/codestar-framework/codestar-framework.php" &&
            $csf_exists
        ) {
            continue;
        }
        // 加载其他文件
        if (file_exists($full_path)) {
            require_once $full_path;
        } else {
            error_log(esc_html__("Text Blind Watermarking Plugin Error: Missing necessary file - ", 'wxs-text-watermarking') . $full_path);
        }
    }
}

// 添加输出JS状态变量的动作
add_action("wp_head", "wxs_watermark_output_js_vars");
function wxs_watermark_output_js_vars()
{
    // 获取用户登录状态
    $is_user_logged_in = is_user_logged_in() ? "true" : "false";
    // 获取当前用户ID
    $current_user_id = "false";
    if (is_user_logged_in()) {
        $user = wp_get_current_user();
        $current_user_id = $user->ID ? (string) $user->ID : "false";
    }
    // 检查是否为文章页面
    $is_article_page = is_single() ? "true" : "false";
    // 输出变量到页面
    echo "<script type='text/javascript'>\n";
    echo "window.wxs_isUserLoggedIn = " . esc_js($is_user_logged_in) . ";\n";
    echo "window.wxs_current_user_id = " . esc_js($current_user_id) . ";\n";
    echo "window.wxs_isArticlePage = " . esc_js($is_article_page) . ";\n";
    echo "</script>\n";
}

// 备用菜单注册函数，当CSF无法正常工作时使用
function wxs_watermark_add_fallback_menu()
{
    $prefix = "wxs_watermark_init_csf_options"; // 使用新前缀
    // 添加顶级菜单
    add_menu_page(
        esc_html__("Text Blind Watermark Configuration", 'wxs-text-watermarking'),
        esc_html__("Text Watermark", 'wxs-text-watermarking'),
        "manage_options",
        $prefix, // 菜单标识使用新前缀
        "wxs_watermark_fallback_page",
        "dashicons-shield",
        58
    );

    // 添加子菜单
    add_submenu_page(
        $prefix,
        esc_html__("Text Blind Watermark Configuration", 'wxs-text-watermarking'),
        esc_html__("Settings", 'wxs-text-watermarking'),
        "manage_options",
        $prefix,
        "wxs_watermark_fallback_page"
    );
}

// 备用页面内容，当CSF无法正常工作时显示
function wxs_watermark_fallback_page()
{
    if (!current_user_can("manage_options")) {
        wp_die(esc_html__("You do not have sufficient permissions to access this page.", 'wxs-text-watermarking'));
    }

    // 检查CSF是否加载
    $csf_loaded = class_exists("CSF") ? esc_html__("Loaded", 'wxs-text-watermarking') : esc_html__("Not Loaded", 'wxs-text-watermarking');
    echo '<div class="wrap">';
    echo "<h1>" . esc_html__("Text Blind Watermark Configuration", 'wxs-text-watermarking') . "</h1>";
    echo '<div class="notice notice-warning">';
    echo "<p>" . esc_html__("Detected that the configuration panel framework did not load properly, possibly due to missing or corrupted files.", 'wxs-text-watermarking') . "</p>";
    echo "<p>" . esc_html__("CSF Framework Status: ", 'wxs-text-watermarking') . esc_html($csf_loaded) . "</p>";
    echo "<p>" . esc_html__("Please check if the ", 'wxs-text-watermarking') . "<code>inc/codestar-framework/</code> " . esc_html__("folder exists and is complete.", 'wxs-text-watermarking') . "</p>";
    echo "<p>" . esc_html__("If the problem persists, please reinstall the plugin.", 'wxs-text-watermarking') . "</p>";
    echo "</div>";
    echo "</div>";
}

// 变体选择器定义
define("VARIATION_SELECTOR_START", 0xfe00);
define("VARIATION_SELECTOR_END", 0xfe0f);
define("VARIATION_SELECTOR_SUPPLEMENT_START", 0xe0100);
define("VARIATION_SELECTOR_SUPPLEMENT_END", 0xe01ef);

// 字节转换为变体选择器字符
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

// 获取客户端IP
function wxs_get_client_ip()
{
    $ip = 'unknown';
    
    // 消毒和验证来自各种标头的IP
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip_list = explode(',', wp_unslash($_SERVER['HTTP_X_FORWARDED_FOR']));
        $ip = sanitize_text_field(trim($ip_list[0])); // 取第一个有效IP并消毒
    } elseif (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = sanitize_text_field(trim(wp_unslash($_SERVER['HTTP_CLIENT_IP'])));
    } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
        $ip = sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR']));
    }
    
    return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : "unknown";
}

// 生成水印内容
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
        $parts[] = "USER:" . ($user->exists() ? $user->ID : esc_html__("guest", 'wxs-text-watermarking'));
    }

    if (!empty($wxs_watermark_config["include_time"])) {
        // 读取 WordPress 全局时区设置
        $wp_timezone_string = get_option("timezone_string"); // 优先获取完整时区（如 Asia/Shanghai）
        $wp_gmt_offset = get_option("gmt_offset"); // 备用：GMT偏移量
        // 构建 WordPress 时区对象
        if (!empty($wp_timezone_string)) {
            // 有完整时区标识符（推荐，如 Asia/Shanghai）
            $timezone = new DateTimeZone($wp_timezone_string);
        } else {
            // 只有GMT偏移量
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

        // 生成 WordPress 当地时间（格式：YYYY-MM-DD HH:MM:SS）
        $wp_local_time = new DateTime("now", $timezone);
        $time_str = $wp_local_time->format("Y-m-d H:i:s");

        // 加入水印内容
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

// 生成盲水印（变体选择器序列）
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

// 计算随机插入次数
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

// 处理单个段落文本
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
        case 1: // 段落末尾插入
            return $text . $watermark;

        case 2: // 随机位置插入
            $insert_count = wxs_calc_random_count($text_length);
            $positions = [];

            // 避免插入次数超过文本长度
            $insert_count = min($insert_count, $text_length - 1);

            for ($i = 0; $i < $insert_count; $i++) {
                do {
                    $pos = wp_rand(1, $text_length - 1);
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

        case 3: // 固定字数插入
            $interval = isset($wxs_watermark_config["fixed_interval"])
                ? $wxs_watermark_config["fixed_interval"]
                : 20;
            $interval = max(5, (int) $interval); // 确保间隔不小于5

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

// 获取配置的HTML标签
function wxs_watermark_get_html_tags()
{
    $tags = wxs_watermark_get_setting("html_tags", "p,li");
    $tags = array_map("trim", explode(",", $tags));
    return array_filter($tags); // 过滤空值
}

// 处理HTML内容中的所有配置标签
function wxs_process_html_content($content)
{
    global $wxs_watermark_config;
    // 检查是否启用
    if (empty($wxs_watermark_config["enable"])) {
        return $content;
    }
    // 调试模式处理
    $isDebug = !empty($wxs_watermark_config["debug_mode"]);
    $rawWatermark = wxs_generate_watermark_raw();
    $watermark = $isDebug
        ? "[" . esc_html__("Watermark Debug PHP Mode:", 'wxs-text-watermarking') . "{$rawWatermark}]"
        : wxs_generate_watermark_selector();

    if (empty($watermark)) {
        return $content;
    }

    // 获取配置的标签
    $tags = wxs_watermark_get_html_tags();
    if (empty($tags)) {
        $tags = ["p", "li"];
    }

    // 处理HTML编码问题
    $content = htmlspecialchars_decode(
        htmlentities($content, ENT_QUOTES, "UTF-8")
    );

    // 使用DOMDocument处理HTML
    $dom = new DOMDocument();
    libxml_use_internal_errors(true); // 禁用libxml错误输出
    $dom->loadHTML(
        '<?xml encoding="UTF-8">' . $content,
        LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
    );
    libxml_clear_errors(); // 清除错误

    $xpath = new DOMXPath($dom);

    // 处理所有配置的标签
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

    // 重建HTML内容
    $html = "";
    foreach ($dom->childNodes as $node) {
        $html .= $dom->saveHTML($node);
    }
    return $html;
}

// 获取消毒后的用户代理
function wxs_get_sanitized_user_agent() {
    if (isset($_SERVER['HTTP_USER_AGENT'])) {
        return sanitize_text_field(wp_unslash($_SERVER['HTTP_USER_AGENT']));
    }
    return '';
}

// 主处理函数 - 根据运行模式决定处理方式
function wxs_watermark_main($content)
{
    global $wxs_watermark_config;

    // 检查是否启用
    if (empty($wxs_watermark_config["enable"])) {
        return $content;
    }

    // 获取运行模式
    $run_mode = isset($wxs_watermark_config["run_mode"])
        ? $wxs_watermark_config["run_mode"]
        : "hybrid";

    // 爬虫过滤
    $user_agent = strtolower(wxs_get_sanitized_user_agent());
    $bot_ua_list = [];
    if (!empty($wxs_watermark_config["bot_ua"])) {
        // 过滤空值和空格
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

    // 如果是爬虫，不添加水印
    if ($is_bot) {
        return $content;
    }

    // 根据运行模式处理
    switch ($run_mode) {
        case "dynamic":
            // 动态模式：纯PHP处理，不管是否为登录状态
            return wxs_process_html_content($content);
            break;

        case "static":
            // 静态模式：纯JS处理，返回原始内容，由JS处理
            return $content;
            break;

        case "hybrid":
            // 混合模式：登录用户用PHP，未登录用户用JS
            $is_logged_in = is_user_logged_in();
            if (!empty($wxs_watermark_config["debug_mode"])) {
                error_log(
                    esc_html__("Hybrid Mode Processing - User Login Status: ", 'wxs-text-watermarking') .
                        ($is_logged_in ? esc_html__("Logged in (PHP processing)", 'wxs-text-watermarking') : esc_html__("Not logged in (JS processing)", 'wxs-text-watermarking'))
                );
            }
            return $is_logged_in
                ? wxs_process_html_content($content)
                : $content;
            break;

        default:
            // 未知模式默认使用混合模式逻辑
            $is_logged_in = is_user_logged_in();
            return $is_logged_in
                ? wxs_process_html_content($content)
                : $content;
            break;
    }
}
// 将内容处理也挂载到init之后
add_action('init', function() {
    add_filter("the_content", "wxs_watermark_main", 999);
});

// 脚本入队与配置本地化 - 根据运行模式决定是否加载JS
add_action("wp_enqueue_scripts", function () {
    global $wxs_watermark_config, $version;

    // 检查是否启用
    if (empty($wxs_watermark_config["enable"])) {
        return;
    }

    // 获取运行模式
    $run_mode = isset($wxs_watermark_config["run_mode"])
        ? $wxs_watermark_config["run_mode"]
        : "hybrid";

    // 爬虫检测
    $user_agent = strtolower(wxs_get_sanitized_user_agent());
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

    // 如果是爬虫，不加载JS
    if ($is_bot) {
        return;
    }

    // 纯JS模式下强制加载JS，无论登录状态
    $load_js = false;
    if ($run_mode === "static") {
        $load_js = true;
        // 调试模式下记录加载信息
        if (!empty($wxs_watermark_config["debug_mode"])) {
            error_log(esc_html__("Pure JS mode enabled, loading watermark script", 'wxs-text-watermarking'));
        }
    } elseif ($run_mode === "dynamic") {
        $load_js = false;
    } else {
        $load_js = !is_user_logged_in();
    }

    // 入队JS文件
    if ($load_js) {
        wp_enqueue_script(
            "wxs-watermark-script",
            WXS_WATERMARK_PLUGIN_URL . "lib/assets/js/index.min.js",
            [],
            $version,
            true
        );

        // 本地化配置
        wxs_output_watermark_config();
    }
});

// 输出配置到前端JS
function wxs_output_watermark_config()
{
    global $wxs_watermark_config;
    if (empty($wxs_watermark_config) || !is_array($wxs_watermark_config)) {
        return;
    }
    // 强制在调试模式下输出详细日志
    $is_debug = !empty($wxs_watermark_config["debug_mode"]);
    if ($is_debug) {
        error_log(
            esc_html__("Watermark Debug Mode Enabled - Configuration Information: ", 'wxs-text-watermarking') .
                print_r($wxs_watermark_config, true)
        );
    }
    // 生成水印内容供JS使用
    $watermark_raw = wxs_generate_watermark_raw();
    // 获取配置的HTML标签
    $html_tags = wxs_watermark_get_html_tags();
    if (empty($html_tags)) {
        $html_tags = ["p", "li"];
    }
    // 格式化配置，确保debug_mode正确传递
    $js_config = [
        "enable" => isset($wxs_watermark_config["enable"])
            ? $wxs_watermark_config["enable"]
            : 0,
        "ip_endpoint" => esc_js(WXS_WATERMARK_PLUGIN_URL . "obtain-an-ip.php"),
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
                ? esc_js($wxs_watermark_config["custom_text"])
                : esc_js(esc_html__("Mr. Wang's Notes All Rights Reserved", 'wxs-text-watermarking')),
        ],
        "htmlTags" => array_map('esc_js', $html_tags),
        "jsGlobalEnable" => isset($wxs_watermark_config["js_global_enable"])
            ? $wxs_watermark_config["js_global_enable"]
            : 0,
        "jsClassSelectors" => isset($wxs_watermark_config["js_class_selectors"])
            ? esc_js($wxs_watermark_config["js_class_selectors"])
            : "",
        "jsIdSelectors" => isset($wxs_watermark_config["js_id_selectors"])
            ? esc_js($wxs_watermark_config["js_id_selectors"])
            : "",
        "global_force_article" => isset(
            $wxs_watermark_config["global_force_article"]
        )
            ? $wxs_watermark_config["global_force_article"]
            : 0,

        "bot_ua" => isset($wxs_watermark_config["bot_ua"])
            ? array_map('esc_js', array_filter(explode("\n", $wxs_watermark_config["bot_ua"])))
            : [],
        "debug_mode" => $is_debug ? 1 : 0, // 确保是数字类型
        "run_mode" => isset($wxs_watermark_config["run_mode"])
            ? esc_js($wxs_watermark_config["run_mode"])
            : "hybrid", // 传递运行模式
    ];

    wp_localize_script(
        "wxs-watermark-script",
        "wxsWatermarkConfig",
        $js_config
    );
}

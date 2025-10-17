<?php
/**
 * 文本盲水印插件 - CSF设置面板配置
 * 
 * @package WXS Text Watermarking
 * @author 大绵羊 天无神话
 * @version 1.0.9
 */

// 防止直接访问
if (!defined('ABSPATH')) exit;

/**
 * 初始化CSF设置面板
 */
function wxs_watermark_init_csf_settings() {
    
    // 只有后台才执行此代码
    if (!is_admin()) {
        return;
    }
    // 检查CSF是否可用
    if (!class_exists('CSF')) {
        return false;
    }
    
    // 刷新所有缓存
    wp_cache_flush();
    
    $version = wxs_watermark_plugin_version();
    $prefix = 'wxs_watermark_init_csf_options';
    
    // 创建设置页面 - 顶部信息
    CSF::createOptions($prefix, [
        'menu_title'      => __('Text Watermark', 'wxs-text-watermarking'),
        'menu_slug'       => $prefix, // 使用前缀作为菜单标识
        'menu_type'       => 'menu',
        'menu_icon'       => 'dashicons-shield',
        'menu_position'   => 58,
        /* translators: %s indicates plugin version number */
        'framework_title' => sprintf(__('Text Blind Watermark Configuration %s', 'wxs-text-watermarking'), '<small style="color: #fff;">v'.$version.'</small>'),
        /* translators: 第一个 %s 是插件作者的网站链接，第二个 %s 是插件版本号 */
        'footer_text'     => sprintf(__('Text Blind Watermark Plugin-<a href="%s" target="_blank">Mr. Wang\'s Notes</a> V%s', 'wxs-text-watermarking'), 'https://wxsnote.cn', $version),
        'show_bar_menu'   => false,
        'theme'           => 'light',
        'show_in_customizer' => false,
        /* translators: %s 表示图标 */
        'footer_credit'   => sprintf(__(' Plugin Author: Tianwu Shenhua %sThank you for using the Text Blind Watermark Plugin ', 'wxs-text-watermarking'), '<i class="fa fa-fw fa-heart-o" aria-hidden="true"></i>'),
    ]);

    // 添加各个设置面板
    wxs_watermark_create_welcome_section($prefix);
    wxs_watermark_create_basic_settings_section($prefix);
    wxs_watermark_create_content_settings_section($prefix);
    wxs_watermark_create_html_settings_section($prefix);
    wxs_watermark_create_bot_filter_section($prefix);
    
    return true;
}

/**
 * 创建欢迎页面（接收前缀参数）
 */
function wxs_watermark_create_welcome_section($prefix) {
    CSF::createSection($prefix, [
        'id'    => 'wxs_watermark_welcome',
        'title' => __('Welcome', 'wxs-text-watermarking'),
        'icon'  => 'fa fa-home',
        'fields' => [
            [
                'type'    => 'submessage',
                'style'   => 'warning',
                'content' => wxs_watermark_get_welcome_content(),
            ],
        ]
    ]);
}

/**
 * 获取欢迎页面内容
 */
function wxs_watermark_get_welcome_content() {
    ob_start();
    ?>
    <div class="wxs-welcome-panel">
        <h3 style="color:#fd4c73;"><i class="fa fa-heart fa-fw"></i> <?php _e('Thank you for using the Text Blind Watermark Plugin', 'wxs-text-watermarking'); ?></h3>
        
        <p><?php _e('Plugin Features: This plugin can embed invisible blind watermarks in article content to help you protect original content.', 'wxs-text-watermarking'); ?></p>
        <div class="wxs-features">
            <div class="feature-box">
                <h4><?php _e('Multiple Insertion Methods', 'wxs-text-watermarking'); ?></h4>
                <p><?php _e('Supports three watermark insertion methods: end of paragraph, random positions, and fixed character intervals', 'wxs-text-watermarking'); ?></p>
            </div>
            <div class="feature-box">
                <h4><?php _e('Custom Watermark Content', 'wxs-text-watermarking'); ?></h4>
                <p><?php _e('Can include visitor IP, user ID, timestamp, and custom text', 'wxs-text-watermarking'); ?></p>
            </div>
            <div class="feature-box">
                <h4><?php _e('Crawler Filtering', 'wxs-text-watermarking'); ?></h4>
                <p><?php _e('Can be set to not insert watermarks for search engine crawlers', 'wxs-text-watermarking'); ?></p>
            </div>
        </div>
        <p><?php _e('Please configure plugin functions through the left tabs. In debug mode, watermarks will be displayed as visible text for testing purposes.', 'wxs-text-watermarking'); ?></p>
        <a href="https://wxsnote.cn/wbmsy" target="_blank" class="wxs-watermark-btn"><i class="fa fa-paper-plane"></i> <?php _e('Go to Extract Watermark', 'wxs-text-watermarking'); ?></a>
        <a href="https://github.com/twsh0305/text_watermarking/releases/latest" target="_blank" class="wxs-watermark-btn"><i class="fa fa-cloud-upload"></i> <?php _e('Check for Updates', 'wxs-text-watermarking'); ?></a>
        <p style="color:red"><?php _e('Minor issue; for example, URLs, if you don\'t add hyperlinks, you should give code highlighting, pure P tag URLs cannot be perfectly adapted', 'wxs-text-watermarking'); ?></p>
        <p><?php _e('Plugin Author: Tianwu Shenhua', 'wxs-text-watermarking'); ?></p>
        <p><?php _e('Author QQ: 2031301686', 'wxs-text-watermarking'); ?></p>
        <p><?php _e('Author Blog: ', 'wxs-text-watermarking'); ?><a href="https://wxsnote.cn/" target="_blank"><?php _e('Mr. Wang\'s Notes', 'wxs-text-watermarking'); ?></a></p>
        <p><?php _e('Co-development: ', 'wxs-text-watermarking'); ?><a href="https://dmyblog.cn/" target="_blank"><?php _e('Big Sheep Blog', 'wxs-text-watermarking'); ?></a></p>
        <p><?php _e('QQ Group: ', 'wxs-text-watermarking'); ?><a href="https://jq.qq.com/?_wv=1027&k=eiGEOg3i" target="_blank">399019539</a></p>
        <p><?php _e('Produced by Tianwu Shenhua, please indicate the open source address when reposting, thank you for your cooperation.', 'wxs-text-watermarking'); ?></p>
        <p style="color:red"><?php _e('Main open source license requirement: Prohibits removal or modification of author information', 'wxs-text-watermarking'); ?></p>
        <p><?php _e('Principle Introduction: ', 'wxs-text-watermarking'); ?><a href="https://wxsnote.cn/6395.html" target="_blank">https://wxsnote.cn/6395.html</a></p>
        <p><?php _e('Open Source Address: ', 'wxs-text-watermarking'); ?><a href="https://github.com/twsh0305/text_watermarking" target="_blank">https://github.com/twsh0305/text_watermarking</a></p>
        <p><?php _e('Plugin Changelog: ', 'wxs-text-watermarking'); ?></p>
        <ul>
        <li>1.0.0 <?php _e('Pure function hook code', 'wxs-text-watermarking'); ?></li>
        <li>1.0.1 <?php _e('Added: Created plugin', 'wxs-text-watermarking'); ?></li>
        <li>1.0.2 <?php _e('Added: Introduced CSF framework, created settings panel', 'wxs-text-watermarking'); ?></li>
        <li>1.0.3 <?php _e('Added: JS control', 'wxs-text-watermarking'); ?></li>
        <li>1.0.4 <?php _e('Fixed: Some WordPress settings panel pages blank', 'wxs-text-watermarking'); ?></li>
        <li>1.0.5 <?php _e('Fixed: Missing CSF framework styles issue', 'wxs-text-watermarking'); ?></li>
        <li>1.0.6 <?php _e('Fixed: File import error', 'wxs-text-watermarking'); ?></li>
        <li>1.0.7 <?php _e('Added: Tag selection, class element selection and ID container selection', 'wxs-text-watermarking'); ?></li>
        <li>1.0.8 <?php _e('Fixed: 1.Use WordPress local time, 2.If Zibll theme exists, directly use Zibll theme\'s CSF framework, 3.Fixed PHP 8.x errors, 4.Fixed global JS not working in articles issue', 'wxs-text-watermarking'); ?></li>
        <li>1.0.9 <?php _e('Fixed: Internationalization of multilingual translation', 'wxs-text-watermarking'); ?></li>
        </ul>
        <p><?php _e('Admin Framework: ', 'wxs-text-watermarking'); ?><a href="https://github.com/Codestar/codestar-framework" target="_blank">Codestar Framework</a> <?php _e('Encryption Solution: ', 'wxs-text-watermarking'); ?><a href="https://github.com/paulgb/emoji-encoder" target="_blank">Emoji Encoder</a></p>
    </div>
    <style>
        .wxs-welcome-panel { padding: 20px; background: #fff; border-radius: 4px; }
        .wxs-features { display: flex; flex-wrap: wrap; gap: 20px; margin: 20px 0; }
        .feature-box { flex: 1; min-width: 200px; padding: 15px; background: #f9f9f9; border-radius: 4px; }
        html body .csf-theme-light .csf-header-inner::before { content: "WXS" !important; }
        /* 基础按钮样式 */
        .wxs-watermark-btn {
          display: inline-block;
          padding: 12px 24px;          /* 内边距，控制按钮大小 */
          background-color: #2196F3;   /* 主色调：蓝色 */
          color: #ffffff;              /* 文字颜色：白色 */
          font-size: 14px;             /* 字体大小 */
          font-weight: 500;            /* 字体粗细，增强可读性 */
          text-align: center;          /* 文字居中 */
          text-decoration: none;       /* 去除下划线 */
          border-radius: 4px;          /* 圆角 */
          border: none;                /* 隐藏边框 */
          cursor: pointer;             /* 悬停时显示手型光标 */
          transition: all 0.3s ease;   /* 过渡动画，增强交互感 */
        }
        
        /* 图标与文字对齐 */
        .wxs-watermark-btn i {
          margin-right: 8px;           /* 图标与文字间距 */
          vertical-align: middle;      /* 垂直居中对齐 */
        }
        
        /* 悬停效果（颜色变深） */
        .wxs-watermark-btn:hover {
          background-color: #1976D2;   /* 悬停颜色：深蓝色 */
          color: #ffffff;              /* 文字颜色：白色 */
          text-decoration: none;       /* 确保无下划线 */
          box-shadow: 0 2px 8px rgba(33, 150, 243, 0.3); /* 可选：添加阴影增强立体感 */
        }
        
        /* 聚焦效果（用于可访问性） */
        .wxs-watermark-btn:focus {
          outline: none;               /* 隐藏默认聚焦边框 */
          box-shadow: 0 0 0 3px rgba(33, 150, 243, 0.2); /* 聚焦时显示浅色轮廓 */
        }
    </style>
    <?php
    return ob_get_clean();
}

/**
 * 创建基础设置面板
 */
function wxs_watermark_create_basic_settings_section($prefix) {
    CSF::createSection($prefix, [
        'title'  => __('Basic Settings', 'wxs-text-watermarking'),
        'icon'   => 'fa fa-cog',
        'fields' => wxs_watermark_get_basic_settings_fields(),
    ]);
}

/**
 * 获取基础设置字段配置
 */
function wxs_watermark_get_basic_settings_fields() {
    return [
        // 基础设置标题
        [
            'type'    => 'heading',
            'content' => __('Basic Settings', 'wxs-text-watermarking'),
        ],
        
        // 启用盲水印开关
        [
            'id'      => 'enable',
            'type'    => 'switcher',
            'title'   => __('Enable Blind Watermark', 'wxs-text-watermarking'),
            'label'   => __('When enabled, blind watermarks will be inserted into article content', 'wxs-text-watermarking'),
            'default' => 0,
        ],
        
        // 运行模式选择
        [
            'id'      => 'run_mode',
            'type'    => 'select',
            'title'   => __('Run Mode', 'wxs-text-watermarking'),
            'options' => [
                'dynamic' => __('Dynamic (Pure PHP)', 'wxs-text-watermarking'),
                'static'  => __('Static (Pure JS)', 'wxs-text-watermarking'),
                'hybrid'  => __('Dynamic-Static Hybrid', 'wxs-text-watermarking'),
            ],
            'default' => 'hybrid',
            'desc'    => __('Dynamic: Pure PHP processing, recommended, ensures no page caching plugins like super cache are installed<br>Static: Pure JS processing, not highly recommended, can be bypassed<br>Dynamic-Static Hybrid: PHP for logged-in users, JS for non-logged-in users (suitable for websites with caching)<br>If JS mode or hybrid mode is enabled, please enable WAF to block fake crawlers', 'wxs-text-watermarking'),
            'dependency' => ['enable', '==', 1],
        ],
        
        // 最小段落长度
        [
            'id'      => 'min_paragraph_length',
            'type'    => 'number',
            'title'   => __('Minimum Paragraph Length', 'wxs-text-watermarking'),
            'desc'    => __('Paragraphs with fewer than this number of characters will not have watermarks inserted (recommended 15-30)', 'wxs-text-watermarking'),
            'default' => 20,
            'min'     => 1,
            'dependency' => ['enable', '==', 1],
        ],
        
        // 插入方式选择
        [
            'id'      => 'insert_method',
            'type'    => 'select',
            'title'   => __('Insertion Method', 'wxs-text-watermarking'),
            'options' => [
                1 => __('Insert at Paragraph End', 'wxs-text-watermarking'),
                2 => __('Insert at Random Positions', 'wxs-text-watermarking'),
                3 => __('Insert at Fixed Character Intervals', 'wxs-text-watermarking'),
            ],
            'default' => 2,
            'desc'    => __('Select how watermarks are inserted into articles', 'wxs-text-watermarking'),
            'dependency' => ['enable', '==', 1],
        ],
        
        // 随机位置插入设置
        [
            'type'    => 'heading',
            'content' => __('Random Position Insertion Settings', 'wxs-text-watermarking'),
            'dependency' => [
                ['enable', '==', 1],
                ['insert_method', '==', 2]
            ],
        ],
        [
            'id'        => 'random_count_type',
            'type'      => 'select',
            'title'     => __('Insertion Count Mode', 'wxs-text-watermarking'),
            'options'   => [
                1 => __('Custom Count', 'wxs-text-watermarking'),
                2 => __('Auto-calculate by Word Count', 'wxs-text-watermarking'),
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
            'title'     => __('Custom Insertion Count', 'wxs-text-watermarking'),
            'desc'      => __('Fixed number of watermark insertions per paragraph', 'wxs-text-watermarking'),
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
            'title'     => __('Word Count Ratio', 'wxs-text-watermarking'),
            'desc'      => __('Add 1 insertion per how many words (e.g., 400 = 1 insertion per 400 words)', 'wxs-text-watermarking'),
            'default'   => 400,
            'min'       => 50,
            'dependency' => [
                ['enable', '==', 1],
                ['insert_method', '==', 2],
                ['random_count_type', '==', 2]
            ],
        ],
        
        // 固定位置插入设置
        [
            'type'    => 'heading',
            'content' => __('Fixed Character Interval Insertion Settings', 'wxs-text-watermarking'),
            'dependency' => [
                ['enable', '==', 1],
                ['insert_method', '==', 3]
            ],
        ],
        [
            'id'      => 'fixed_interval',
            'type'    => 'number',
            'title'   => __('Insertion Interval', 'wxs-text-watermarking'),
            'desc'    => __('Insert watermark every how many characters', 'wxs-text-watermarking'),
            'default' => 20,
            'min'     => 5,
            'dependency' => [
                ['enable', '==', 1],
                ['insert_method', '==', 3]
            ],
        ],
        
        // 调试模式
        [
            'id'      => 'debug_mode',
            'type'    => 'switcher',
            'title'   => __('Debug Mode', 'wxs-text-watermarking'),
            'label'   => __('When enabled, watermarks will be displayed as visible text ([Watermark Debug:...])', 'wxs-text-watermarking'),
            'default' => 0,
            'desc'    => __('Used for testing watermark effects, recommended to disable in production environment', 'wxs-text-watermarking'),
            'dependency' => ['enable', '==', 1],
        ],
    ];
}

/**
 * 创建水印内容设置面板
 */
function wxs_watermark_create_content_settings_section($prefix) {
    CSF::createSection($prefix, [
        'title'  => __('Watermark Content Settings', 'wxs-text-watermarking'),
        'icon'   => 'fa fa-file-text',
        'fields' => wxs_watermark_get_content_settings_fields()
    ]);
}

/**
 * 获取水印内容设置字段配置
 */
function wxs_watermark_get_content_settings_fields() {
    return [
        [
            'id'      => 'include_ip',
            'type'    => 'switcher',
            'title'   => __('Include Visitor IP', 'wxs-text-watermarking'),
            'label'   => __('Visitor\'s IP address, for user traceability and positioning', 'wxs-text-watermarking'),
            'default' => 1,
        ],
        [
            'id'      => 'include_user',
            'type'    => 'switcher',
            'title'   => __('Include User ID', 'wxs-text-watermarking'),
            'label'   => __('Show ID for logged-in users, show "guest" for visitors', 'wxs-text-watermarking'),
            'default' => 1,
        ],
        [
            'id'      => 'include_time',
            'type'    => 'switcher',
            'title'   => __('Include Timestamp', 'wxs-text-watermarking'),
            'label'   => __('Time when watermark was generated (YYYY-MM-DD HH:MM:SS)', 'wxs-text-watermarking'),
            'default' => 1,
        ],
        [
            'id'      => 'include_custom',
            'type'    => 'switcher',
            'title'   => __('Include Custom Text', 'wxs-text-watermarking'),
            'default' => 1,
        ],
        [
            'id'        => 'custom_text',
            'type'      => 'text',
            'title'     => __('Custom Text Content', 'wxs-text-watermarking'),
            'desc'      => __('Recommended to include copyright information (e.g., "XX All Rights Reserved")', 'wxs-text-watermarking'),
            'default'   => (get_bloginfo('name') ? get_bloginfo('name') . __(' All Rights Reserved', 'wxs-text-watermarking') : __('Mr. Wang\'s Notes All Rights Reserved', 'wxs-text-watermarking')),
            'dependency' => [
                ['include_custom', '==', 1]
            ],
        ],
    ];
}

/**
 * 创建高级设置面板
 */
function wxs_watermark_create_html_settings_section($prefix) {
    CSF::createSection($prefix, [
        'title'  => __('Advanced Settings', 'wxs-text-watermarking'),
        'icon'   => 'fa fa-cogs',
        'fields' => wxs_watermark_get_html_tags_fields(),
    ]);
}

/**
 * 获取高级设置字段配置
 */
function wxs_watermark_get_html_tags_fields() {
    return [
        [
            'id'      => 'html_tags',
            'type'    => 'text',
            'title'   => __('Processed HTML Tags', 'wxs-text-watermarking'),
            'desc'    => __('Enter HTML tags to process, separated by commas <code>,</code>, articles only, not recursive, only one level, defaults to <code>p</code>,<code>li</code><br>Common article tags: <code>h2</code> to <code>h6</code>,<code>p</code>,<code>li</code>,<code>span</code>,<code>strong</code>,<code>em</code>,<code>b</code>,<code>i</code>,<code>blockquote</code>,<code>q</code>, etc.<br>Not recommended: <code>code</code>,<code>pre</code>', 'wxs-text-watermarking'),
            'default' => 'p,li',
        ],
        [
            'id'      => 'js_global_enable',
            'type'    => 'switcher',
            'title'   => __('JS Global Processing Switch', 'wxs-text-watermarking'),
            'label'   => __('When enabled, content outside articles will be processed based on the selectors below, disabled by default', 'wxs-text-watermarking'),
            'default' => 0,
        ],
        [
            'id'        => 'js_class_selectors',
            'type'      => 'text',
            'title'     => __('Class Selector Settings', 'wxs-text-watermarking'),
            'desc'      => __('JS only, recursively processes all within tags, format example: <code>.css1</code>,<code>.css2</code>,<code>p.css3</code>,<code>span.css4</code>', 'wxs-text-watermarking'),
            'default'   => '',
            'dependency' => [
                ['js_global_enable', '==', 1]
            ],
        ],
        [
            'id'        => 'js_id_selectors',
            'type'      => 'text',
            'title'     => __('ID Selector Settings', 'wxs-text-watermarking'),
            'desc'      => __('JS only, recursively processes all within tags, format example: <code>#id1</code>,<code>#id2</code>, note: CSS selector specification does not allow IDs starting with numbers (like <code>#123</code>)', 'wxs-text-watermarking'),
            'default'   => '',
            'dependency' => [
                ['js_global_enable', '==', 1]
            ],
        ],
        [
            'id'        => 'global_force_article',
            'type'      => 'switcher',
            'title'     => __('Force Enable Global Selectors on Article Pages', 'wxs-text-watermarking'),
            'label'     => __('When enabled, even on article pages, global selector matched elements will take effect', 'wxs-text-watermarking'),
            'desc'      => __('Requires "JS Global Processing Switch" to be enabled first, otherwise this setting has no effect', 'wxs-text-watermarking'),
            'default'   => 0, // 默认禁用
            'dependency' => [
                ['js_global_enable', '==', 1] // 只有全局处理启用时才显示
            ],
        ],
    ];
}

/**
 * 创建爬虫过滤设置面板
 */
function wxs_watermark_create_bot_filter_section($prefix) {
    CSF::createSection($prefix, [
        'title'  => __('Crawler Filter Whitelist', 'wxs-text-watermarking'),
        'icon'   => 'fa fa-bug',
        'fields' => wxs_watermark_get_bot_filter_fields()
    ]);
}

/**
 * 获取爬虫过滤设置字段配置
 */
function wxs_watermark_get_bot_filter_fields() {
    return [
        [
            'id'      => 'bot_ua',
            'type'    => 'textarea',
            'attributes'  => array(
                    'rows' => 5,
                ),
            'title'   => __('Crawler UA List', 'wxs-text-watermarking'),
            'desc'    => __('One crawler identifier per line, no watermarks inserted when matched, no matching when empty, used to prevent search engines from crawling incorrectly, recommended to use with WAF to block fake crawlers.', 'wxs-text-watermarking'),
            'default' => "googlebot\nbingbot\nbaiduspider\nsogou web spider\n360spider\nyisouspider\nbytespider\nduckduckbot\nyandexbot\nyahoo",
        ],
    ];
}

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
        'menu_title'      => esc_html__('Text Watermark', 'wxs-text-watermarking'),
        'menu_slug'       => $prefix, // 使用前缀作为菜单标识
        'menu_type'       => 'menu',
        'menu_icon'       => 'dashicons-shield',
        'menu_position'   => 58,
        /* translators: %s: 插件版本号 */
        'framework_title' => sprintf(esc_html__('Text Blind Watermark Configuration %s', 'wxs-text-watermarking'), '<small style="color: #fff;">v'.esc_html($version).'</small>'),
        'footer_text'     => sprintf(
            wp_kses(
                /* translators: %1$s: 插件作者网站链接, %2$s: 插件版本号 */
                __('Text Blind Watermark Plugin-<a href="%1$s" target="_blank">Mr. Wang\'s Notes</a> V%2$s', 'wxs-text-watermarking'),
                [
                    'a' => [
                        'href' => [],
                        'target' => []
                    ]
                ]
            ), 
            esc_url('https://wxsnote.cn'), 
            esc_html($version)
        ),
        'show_bar_menu'   => false,
        'theme'           => 'light',
        'show_in_customizer' => false,
        'footer_credit'   => sprintf(
            /* translators: %s: 心形图标 */
            esc_html__(' Plugin Author: Tianwu Shenhua %sThank you for using the Text Blind Watermark Plugin ', 'wxs-text-watermarking'),
            '<i class="fa fa-fw fa-heart-o" aria-hidden="true"></i>'
        ),
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
        'title' => esc_html__('Welcome', 'wxs-text-watermarking'),
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
        <h3 style="color:#fd4c73;"><i class="fa fa-heart fa-fw"></i> <?php echo esc_html__('Thank you for using the Text Blind Watermark Plugin', 'wxs-text-watermarking'); ?></h3>
        
        <p><?php echo esc_html__('Plugin Features: This plugin can embed invisible blind watermarks in article content to help you protect original content.', 'wxs-text-watermarking'); ?></p>
        <div class="wxs-features">
            <div class="feature-box">
                <h4><?php echo esc_html__('Multiple Insertion Methods', 'wxs-text-watermarking'); ?></h4>
                <p><?php echo esc_html__('Supports three watermark insertion methods: end of paragraph, random positions, and fixed character intervals', 'wxs-text-watermarking'); ?></p>
            </div>
            <div class="feature-box">
                <h4><?php echo esc_html__('Custom Watermark Content', 'wxs-text-watermarking'); ?></h4>
                <p><?php echo esc_html__('Can include visitor IP, user ID, timestamp, and custom text', 'wxs-text-watermarking'); ?></p>
            </div>
            <div class="feature-box">
                <h4><?php echo esc_html__('Crawler Filtering', 'wxs-text-watermarking'); ?></h4>
                <p><?php echo esc_html__('Can be set to not insert watermarks for search engine crawlers', 'wxs-text-watermarking'); ?></p>
            </div>
        </div>
        <p><?php echo esc_html__('Please configure plugin functions through the left tabs. In debug mode, watermarks will be displayed as visible text for testing purposes.', 'wxs-text-watermarking'); ?></p>
        <a href="https://wxsnote.cn/wbmsy" target="_blank" class="wxs-watermark-btn"><i class="fa fa-paper-plane"></i> <?php echo esc_html__('Go to Extract Watermark', 'wxs-text-watermarking'); ?></a>
        <a href="https://github.com/twsh0305/text_watermarking/releases/latest" target="_blank" class="wxs-watermark-btn"><i class="fa fa-cloud-upload"></i> <?php echo esc_html__('Check for Updates', 'wxs-text-watermarking'); ?></a>
        <p style="color:red"><?php echo esc_html__('Minor issue; for example, URLs, if you don\'t add hyperlinks, you should give code highlighting, pure P tag URLs cannot be perfectly adapted', 'wxs-text-watermarking'); ?></p>
        <p><?php echo esc_html__('Plugin Author: Tianwu Shenhua', 'wxs-text-watermarking'); ?></p>
        <p><?php echo esc_html__('Author QQ: 2031301686', 'wxs-text-watermarking'); ?></p>
        <p><?php echo esc_html__('Author Blog: ', 'wxs-text-watermarking'); ?><a href="https://wxsnote.cn/" target="_blank"><?php echo esc_html__('Mr. Wang\'s Notes', 'wxs-text-watermarking'); ?></a></p>
        <p><?php echo esc_html__('Co-development: ', 'wxs-text-watermarking'); ?><a href="https://dmyblog.cn/" target="_blank"><?php echo esc_html__('Big Sheep Blog', 'wxs-text-watermarking'); ?></a></p>
        <p><?php echo esc_html__('QQ Group: ', 'wxs-text-watermarking'); ?><a href="https://jq.qq.com/?_wv=1027&k=eiGEOg3i" target="_blank">399019539</a></p>
        <p><?php echo esc_html__('Produced by Tianwu Shenhua, please indicate the open source address when reposting, thank you for your cooperation.', 'wxs-text-watermarking'); ?></p>
        <p style="color:red"><?php echo esc_html__('Main open source license requirement: Prohibits removal or modification of author information', 'wxs-text-watermarking'); ?></p>
        <p><?php echo esc_html__('Principle Introduction: ', 'wxs-text-watermarking'); ?><a href="https://wxsnote.cn/6395.html" target="_blank">https://wxsnote.cn/6395.html</a></p>
        <p><?php echo esc_html__('Open Source Address: ', 'wxs-text-watermarking'); ?><a href="https://github.com/twsh0305/text_watermarking" target="_blank">https://github.com/twsh0305/text_watermarking</a></p>
        <p><?php echo esc_html__('Admin Framework: ', 'wxs-text-watermarking'); ?><a href="https://github.com/Codestar/codestar-framework" target="_blank">Codestar Framework</a> <?php echo esc_html__('Encryption Solution: ', 'wxs-text-watermarking'); ?><a href="https://github.com/paulgb/emoji-encoder" target="_blank">Emoji Encoder</a></p>
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
        'title'  => esc_html__('Basic Settings', 'wxs-text-watermarking'),
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
            'content' => esc_html__('Basic Settings', 'wxs-text-watermarking'),
        ],
        
        // 启用盲水印开关
        [
            'id'      => 'enable',
            'type'    => 'switcher',
            'title'   => esc_html__('Enable Blind Watermark', 'wxs-text-watermarking'),
            'label'   => esc_html__('When enabled, blind watermarks will be inserted into article content', 'wxs-text-watermarking'),
            'default' => 0,
        ],
        
        // 运行模式选择
        [
            'id'      => 'run_mode',
            'type'    => 'select',
            'title'   => esc_html__('Run Mode', 'wxs-text-watermarking'),
            'options' => [
                'dynamic' => esc_html__('Dynamic (Pure PHP)', 'wxs-text-watermarking'),
                'static'  => esc_html__('Static (Pure JS)', 'wxs-text-watermarking'),
                'hybrid'  => esc_html__('Dynamic-Static Hybrid', 'wxs-text-watermarking'),
            ],
            'default' => 'hybrid',
            'desc'    => wp_kses(
                __('Dynamic: Pure PHP processing, recommended, ensures no page caching plugins like super cache are installed<br>Static: Pure JS processing, not highly recommended, can be bypassed<br>Dynamic-Static Hybrid: PHP for logged-in users, JS for non-logged-in users (suitable for websites with caching)<br>If JS mode or hybrid mode is enabled, please enable WAF to block fake crawlers', 'wxs-text-watermarking'),
                ['br' => []]
            ),
            'dependency' => ['enable', '==', 1],
        ],
        
        // 最小段落长度
        [
            'id'      => 'min_paragraph_length',
            'type'    => 'number',
            'title'   => esc_html__('Minimum Paragraph Length', 'wxs-text-watermarking'),
            'desc'    => esc_html__('Paragraphs with fewer than this number of characters will not have watermarks inserted (recommended 15-30)', 'wxs-text-watermarking'),
            'default' => 20,
            'min'     => 1,
            'dependency' => ['enable', '==', 1],
        ],
        
        // 插入方式选择
        [
            'id'      => 'insert_method',
            'type'    => 'select',
            'title'   => esc_html__('Insertion Method', 'wxs-text-watermarking'),
            'options' => [
                1 => esc_html__('Insert at Paragraph End', 'wxs-text-watermarking'),
                2 => esc_html__('Insert at Random Positions', 'wxs-text-watermarking'),
                3 => esc_html__('Insert at Fixed Character Intervals', 'wxs-text-watermarking'),
            ],
            'default' => 2,
            'desc'    => esc_html__('Select how watermarks are inserted into articles', 'wxs-text-watermarking'),
            'dependency' => ['enable', '==', 1],
        ],
        
        // 随机位置插入设置
        [
            'type'    => 'heading',
            'content' => esc_html__('Random Position Insertion Settings', 'wxs-text-watermarking'),
            'dependency' => [
                ['enable', '==', 1],
                ['insert_method', '==', 2]
            ],
        ],
        [
            'id'        => 'random_count_type',
            'type'      => 'select',
            'title'     => esc_html__('Insertion Count Mode', 'wxs-text-watermarking'),
            'options'   => [
                1 => esc_html__('Custom Count', 'wxs-text-watermarking'),
                2 => esc_html__('Auto-calculate by Word Count', 'wxs-text-watermarking'),
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
            'title'     => esc_html__('Custom Insertion Count', 'wxs-text-watermarking'),
            'desc'      => esc_html__('Fixed number of watermark insertions per paragraph', 'wxs-text-watermarking'),
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
            'title'     => esc_html__('Word Count Ratio', 'wxs-text-watermarking'),
            'desc'      => esc_html__('Add 1 insertion per how many words (e.g., 400 = 1 insertion per 400 words)', 'wxs-text-watermarking'),
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
            'content' => esc_html__('Fixed Character Interval Insertion Settings', 'wxs-text-watermarking'),
            'dependency' => [
                ['enable', '==', 1],
                ['insert_method', '==', 3]
            ],
        ],
        [
            'id'      => 'fixed_interval',
            'type'    => 'number',
            'title'   => esc_html__('Insertion Interval', 'wxs-text-watermarking'),
            'desc'    => esc_html__('Insert watermark every how many characters', 'wxs-text-watermarking'),
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
            'title'   => esc_html__('Debug Mode', 'wxs-text-watermarking'),
            'label'   => esc_html__('When enabled, watermarks will be displayed as visible text ([Watermark Debug:...])', 'wxs-text-watermarking'),
            'default' => 0,
            'desc'    => esc_html__('Used for testing watermark effects, recommended to disable in production environment', 'wxs-text-watermarking'),
            'dependency' => ['enable', '==', 1],
        ],
    ];
}

/**
 * 创建水印内容设置面板
 */
function wxs_watermark_create_content_settings_section($prefix) {
    CSF::createSection($prefix, [
        'title'  => esc_html__('Watermark Content Settings', 'wxs-text-watermarking'),
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
            'title'   => esc_html__('Include Visitor IP', 'wxs-text-watermarking'),
            'label'   => esc_html__('Visitor\'s IP address, for user traceability and positioning', 'wxs-text-watermarking'),
            'default' => 1,
        ],
        [
            'id'      => 'include_user',
            'type'    => 'switcher',
            'title'   => esc_html__('Include User ID', 'wxs-text-watermarking'),
            'label'   => esc_html__('Show ID for logged-in users, show "guest" for visitors', 'wxs-text-watermarking'),
            'default' => 1,
        ],
        [
            'id'      => 'include_time',
            'type'    => 'switcher',
            'title'   => esc_html__('Include Timestamp', 'wxs-text-watermarking'),
            'label'   => esc_html__('Time when watermark was generated (YYYY-MM-DD HH:MM:SS)', 'wxs-text-watermarking'),
            'default' => 1,
        ],
        [
            'id'      => 'include_custom',
            'type'    => 'switcher',
            'title'   => esc_html__('Include Custom Text', 'wxs-text-watermarking'),
            'default' => 1,
        ],
        [
            'id'        => 'custom_text',
            'type'      => 'text',
            'title'     => esc_html__('Custom Text Content', 'wxs-text-watermarking'),
            'desc'      => esc_html__('Recommended to include copyright information (e.g., "XX All Rights Reserved")', 'wxs-text-watermarking'),
            'default'   => (get_bloginfo('name') ? esc_html(get_bloginfo('name')) . esc_html__(' All Rights Reserved', 'wxs-text-watermarking') : esc_html__('Mr. Wang\'s Notes All Rights Reserved', 'wxs-text-watermarking')),
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
        'title'  => esc_html__('Advanced Settings', 'wxs-text-watermarking'),
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
            'title'   => esc_html__('Processed HTML Tags', 'wxs-text-watermarking'),
            'desc'    => wp_kses(
                __('Enter HTML tags to process, separated by commas <code>,</code>, articles only, not recursive, only one level, defaults to <code>p</code>,<code>li</code><br>Common article tags: <code>h2</code> to <code>h6</code>,<code>p</code>,<code>li</code>,<code>span</code>,<code>strong</code>,<code>em</code>,<code>b</code>,<code>i</code>,<code>blockquote</code>,<code>q</code>, etc.<br>Not recommended: <code>code</code>,<code>pre</code>', 'wxs-text-watermarking'),
                [
                    'br' => [],
                    'code' => []
                ]
            ),
            'default' => 'p,li',
        ],
        [
            'id'      => 'js_global_enable',
            'type'    => 'switcher',
            'title'   => esc_html__('JS Global Processing Switch', 'wxs-text-watermarking'),
            'label'   => esc_html__('When enabled, content outside articles will be processed based on the selectors below, disabled by default', 'wxs-text-watermarking'),
            'default' => 0,
        ],
        [
            'id'        => 'js_class_selectors',
            'type'      => 'text',
            'title'     => esc_html__('Class Selector Settings', 'wxs-text-watermarking'),
            'desc'      => wp_kses(
                __('JS only, recursively processes all within tags, format example: <code>.css1</code>,<code>.css2</code>,<code>p.css3</code>,<code>span.css4</code>', 'wxs-text-watermarking'),
                ['code' => []]
            ),
            'default'   => '',
            'dependency' => [
                ['js_global_enable', '==', 1]
            ],
        ],
        [
            'id'        => 'js_id_selectors',
            'type'      => 'text',
            'title'     => esc_html__('ID Selector Settings', 'wxs-text-watermarking'),
            'desc'      => wp_kses(
                __('JS only, recursively processes all within tags, format example: <code>#id1</code>,<code>#id2</code>, note: CSS selector specification does not allow IDs starting with numbers (like <code>#123</code>)', 'wxs-text-watermarking'),
                ['code' => []]
            ),
            'default'   => '',
            'dependency' => [
                ['js_global_enable', '==', 1]
            ],
        ],
        [
            'id'        => 'global_force_article',
            'type'      => 'switcher',
            'title'     => esc_html__('Force Enable Global Selectors on Article Pages', 'wxs-text-watermarking'),
            'label'     => esc_html__('When enabled, even on article pages, global selector matched elements will take effect', 'wxs-text-watermarking'),
            'desc'      => esc_html__('Requires "JS Global Processing Switch" to be enabled first, otherwise this setting has no effect', 'wxs-text-watermarking'),
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
        'title'  => esc_html__('Crawler Filter Whitelist', 'wxs-text-watermarking'),
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
            'title'   => esc_html__('Crawler UA List', 'wxs-text-watermarking'),
            'desc'    => esc_html__('One crawler identifier per line, no watermarks inserted when matched, no matching when empty, used to prevent search engines from crawling incorrectly, recommended to use with WAF to block fake crawlers.', 'wxs-text-watermarking'),
            'default' => "googlebot\nbingbot\nbaiduspider\nsogou web spider\n360spider\nyisouspider\nbytespider\nduckduckbot\nyandexbot\nyahoo",
        ],
    ];
}

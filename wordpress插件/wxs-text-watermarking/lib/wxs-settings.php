<?php
/**
 * 文本盲水印插件 - CSF设置面板配置
 * 
 * @package WXS Text Watermarking
 * @author 大绵羊 天无神话
 * @version 1.0.7
 */

// 防止直接访问
if (!defined('ABSPATH')) exit;

/**
 * 初始化CSF设置面板
 */
function wxs_watermark_init_csf_settings() {
    // 检查CSF是否可用
    if (!class_exists('CSF')) {
        return false;
    }
    
    $version = wxs_watermark_plugin_version();
    
    // 创建设置页面 - 顶部信息
    CSF::createOptions('wxs_watermark_settings', [
        'menu_title'      => '文本水印',
        'menu_slug'       => 'wxs-watermark',
        'menu_type'       => 'menu',
        'menu_icon'       => 'dashicons-shield',
        'menu_position'   => 58,
        'framework_title' => '文本盲水印配置 <small style="color: #fff;">v'.$version.'</small>',
        'footer_text'     => '文本盲水印插件-<a href="https://wxsnote.cn" target="_blank">王先生笔记</a> V'.$version,
        'show_bar_menu'   => false,
        'theme'           => 'light',
    ]);

    // 添加各个设置面板
    wxs_watermark_create_welcome_section();
    wxs_watermark_create_basic_settings_section();
    wxs_watermark_create_content_settings_section();
    wxs_watermark_create_html_settings_section();
    wxs_watermark_create_bot_filter_section();
    
    return true;
}

/**
 * 创建欢迎页面
 */
function wxs_watermark_create_welcome_section() {
    CSF::createSection('wxs_watermark_settings', [
        'id'    => 'wxs_watermark_welcome',
        'title' => '欢迎使用',
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
    return '
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
        <a href="https://wxsnote.cn/wbmsy" target="_blank" class="wxs-watermark-btn"><i class="fa fa-paper-plane-o"></i> 前往提取水印</a>
        <p style="color:red">小问题；例如网址，你不加超链接，你就给个代码高亮啊，纯P标签的网址，这个是无法完美适配的</p>
        <p>插件作者：天无神话</p>
        <p>作者QQ：2031301686</p>
        <p>作者博客：<a href="https://wxsnote.cn/" target="_blank">王先生笔记</a></p>
        <p>共同开发：<a href="https://https://dmyblog.cn/" target="_blank">大绵羊博客</a></p>
        <p>QQ群：<a href="https://jq.qq.com/?_wv=1027&k=eiGEOg3i" target="_blank">399019539</a></p>
        <p>天无神话制作，转载请注明开源地址，谢谢合作。</p>
        <p style="color:red">开源协议主要要求：禁止移除或修改作者信息</p>
        <p>原理介绍：<a href="https://wxsnote.cn/6395.html" target="_blank">https://wxsnote.cn/6395.html</a></p>
        <p>开源地址：<a href="https://github.com/twsh0305/text_watermarking" target="_blank">https://github.com/twsh0305/text_watermarking</a></p>
        <p>插件日志：</p>
        <ul>
        <li>1.0.0 纯function钩子代码</li>
        <li>1.0.1 创建插件</li>
        <li>1.0.2 引入CSF框架，创建设置面板</li>
        <li>1.0.3 新增js控制 20250806发布</li>
        <li>1.0.4 修复部分wordpress设置面板页面空白</li>
        <li>1.0.5 修复CSF框架缺失样式的问题</li>
        <li>1.0.6 修复引入文件错误</li>
        <li>1.0.7 增加标签选择，class元素选择及id容器选择 20250806发布</li>
        <li>计划：更新时区选择，标签白名单</li>
        </ul>
        <p>后台框架：<a href="https://github.com/Codestar/codestar-framework" target="_blank">Codestar Framework</a> 加密方案：<a href="https://github.com/paulgb/emoji-encoder" target="_blank">Emoji Encoder</a></p>
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
          background-color: #2196F3;   /* 主色：蓝色 */
          color: #ffffff;              /* 文字颜色：白色 */
          font-size: 14px;             /* 文字大小 */
          font-weight: 500;            /* 文字加粗，提升辨识度 */
          text-align: center;          /* 文字居中 */
          text-decoration: none;       /* 去除下划线 */
          border-radius: 4px;          /* 圆角 */
          border: none;                /* 隐藏边框 */
          cursor: pointer;             /* 鼠标悬停显示手型 */
          transition: all 0.3s ease;   /* 过渡动画，提升交互感 */
        }
        
        /* 图标与文字对齐 */
        .wxs-watermark-btn i {
          margin-right: 8px;           /* 图标与文字间距 */
          vertical-align: middle;      /* 垂直居中对齐 */
        }
        
        /* 悬停效果（加深颜色） */
        .wxs-watermark-btn:hover {
          background-color: #1976D2;   /*  hover 色：更深的蓝色 */
          color: #ffffff;              /* 文字颜色：白色 */
          text-decoration: none;       /* 确保无下划线 */
          box-shadow: 0 2px 8px rgba(33, 150, 243, 0.3); /* 可选：添加阴影增强层次感 */
        }
        
        /* 聚焦效果（适配辅助设备） */
        .wxs-watermark-btn:focus {
          outline: none;               /* 隐藏默认聚焦边框 */
          box-shadow: 0 0 0 3px rgba(33, 150, 243, 0.2); /* 聚焦时显示浅色外框 */
        }
    </style>';
}

/**
 * 创建基础设置面板
 */
function wxs_watermark_create_basic_settings_section() {
    CSF::createSection('wxs_watermark_settings', [
        'title'  => '基础设置',
        'icon'   => 'fa fa-cog',
        'fields' => wxs_watermark_get_basic_settings_fields(),
    ]);
}

/**
 * 获取基础设置字段配置
 */
function wxs_watermark_get_basic_settings_fields() {
    return [
        // 基本设置标题
        [
            'type'    => 'heading',
            'content' => '基本设置',
        ],
        
        // 启用盲水印开关
        [
            'id'      => 'enable',
            'type'    => 'switcher',
            'title'   => '启用盲水印',
            'label'   => '开启后将在文章内容中插入盲水印',
            'default' => 0,
        ],
        
        // 运行模式选择
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
            'desc'    => '动态：纯PHP处理，推荐，确保没有安装如super cache页面缓存类似插件<br>静态：纯JS处理，不是很推荐，可被绕过<br>动静混合：登录用户用PHP，未登录用户用JS（适合有缓存的网站）<br>若开启JS模式或混合模式下，请开启WAF拦截假蜘蛛',
            'dependency' => ['enable', '==', 1],
        ],
        
        // 最小段落字数
        [
            'id'      => 'min_paragraph_length',
            'type'    => 'number',
            'title'   => '最小段落字数',
            'desc'    => '少于此字数的段落不插入水印（建议15-30）',
            'default' => 20,
            'min'     => 1,
            'dependency' => ['enable', '==', 1],
        ],
        
        // 插入方式选择
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
        
        // 随机位置插入设置
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
        
        // 固定位置插入设置
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
        
        // 调试模式
        [
            'id'      => 'debug_mode',
            'type'    => 'switcher',
            'title'   => '调试模式',
            'label'   => '启用后水印将以可见文本形式显示（[水印调试:...]）',
            'default' => 0,
            'desc'    => '用于测试水印效果，正式环境建议关闭',
            'dependency' => ['enable', '==', 1],
        ],
    ];
}

/**
 * 创建水印内容设置面板
 */
function wxs_watermark_create_content_settings_section() {
    CSF::createSection('wxs_watermark_settings', [
        'title'  => '水印内容设置',
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
    ];
}



function wxs_watermark_create_html_settings_section() {
    CSF::createSection('wxs_watermark_settings', [
        'title'  => '高级设置',
        'icon'   => 'fa fa-cogs',
        'fields' => wxs_watermark_get_html_tags_fields(),
    ]);
}



function wxs_watermark_get_html_tags_fields() {
    return [
        [
            'id'      => 'html_tags',
            'type'    => 'text',
            'title'   => '处理的HTML标签',
            'desc'    => '输入要处理的HTML标签，用逗号<code>,</code>分隔，仅文章，默认是<code>p</code>,<code>li</code><br>文章常见标签：<code>h2</code>到<code>h6</code>,<code>p</code>,<code>li</code>,<code>span</code>,<code>strong</code>,<code>em</code>,<code>b</code>,<code>i</code>,<code>blockquote</code>,<code>q</code>,等<br>不推荐：<code>code</code>,<code>pre</code>',
            'default' => 'p,li',
        ],
        [
            'id'      => 'js_global_enable',
            'type'    => 'switcher',
            'title'   => 'JS全局处理开关',
            'label'   => '开启后将根据下方选择器处理文章以外的内容，默认关闭',
            'default' => 0,
        ],
        [
            'id'        => 'js_class_selectors',
            'type'      => 'text',
            'title'     => 'Class选择器设置',
            'desc'      => '仅JS生效，格式示例：<code>.css1</code>,<code>.css2</code>,<code>p.css3</code>,<code>span.css4</code>',
            'default'   => '',
            'dependency' => [
                ['js_global_enable', '==', 1]
            ],
        ],
        [
            'id'        => 'js_id_selectors',
            'type'      => 'text',
            'title'     => 'ID选择器设置',
            'desc'      => '仅JS生效，格式示例：<code>#id1</code>,<code>#id2</code>',
            'default'   => '',
            'dependency' => [
                ['js_global_enable', '==', 1]
            ],
        ],
    ];
}


/**
 * 创建爬虫过滤设置面板
 */
function wxs_watermark_create_bot_filter_section() {
    CSF::createSection('wxs_watermark_settings', [
        'title'  => '爬虫过滤白名单',
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
            'title'   => '爬虫UA列表',
            'desc'    => '每行一个爬虫标识，匹配时不插入水印，清空时不匹配，用于防止搜索引擎抓取错误，建议配合WAF使用，拦截假蜘蛛。',
            'default' => "googlebot\nbingbot\nbaiduspider\nsogou web spider\n360spider\nyisouspider\nbytespider\nduckduckbot\nyandexbot\nyahoo",
        ],
    ];
}

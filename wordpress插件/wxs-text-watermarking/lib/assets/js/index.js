document.addEventListener('DOMContentLoaded', function() {
    // 页面状态变量
    const wxstbw_isUserLoggedIn = window.wxstbw_isUserLoggedIn || false;
    const wxstbw_current_user_id = window.wxstbw_current_user_id || false;
    const wxstbw_current_user_roles = window.wxstbw_current_user_roles || [];
    const wxstbw_isArticlePage = window.wxstbw_isArticlePage || false;

    // 加载配置
    const config = window.wxstbwConfig || {};
    
    // 配置规范化（统一处理布尔值类型）
    const normalizedConfig = {
        ...config,
        enable: !!Number(config.enable),
        user_group_enable: !!Number(config.user_group_enable || 0),
        user_group_type: config.user_group_type || 'wordpress',
        wordpress_user_roles: Array.isArray(config.wordpress_user_roles) ? config.wordpress_user_roles : [],
        jsGlobalEnable: !!Number(config.jsGlobalEnable),
        global_force_article: !!Number(config.global_force_article),
        debug_mode: !!Number(config.debug_mode),
        watermark_content: {
            include_ip: !!Number(config.watermark_content?.include_ip || 0),
            include_user: !!Number(config.watermark_content?.include_user || 0),
            include_time: !!Number(config.watermark_content?.include_time || 0),
            include_custom: !!Number(config.watermark_content?.include_custom || 0),
            custom_text: config.watermark_content?.custom_text || ''
        },
        min_paragraph_length: parseInt(config.min_paragraph_length) || 20,
        insert_method: parseInt(config.insert_method) || 2,
        random: {
            count_type: parseInt(config.random?.count_type) || 2,
            custom_count: parseInt(config.random?.custom_count) || 1,
            word_based_ratio: parseInt(config.random?.word_based_ratio) || 400
        },
        fixed: {
            interval: parseInt(config.fixed?.interval) || 20
        },
        htmlTags: Array.isArray(config.htmlTags) ? config.htmlTags : ['p', 'li'],
        bot_ua: Array.isArray(config.bot_ua) ? config.bot_ua : [],
        run_mode: config.run_mode || 'hybrid'
    };
    
    const isDebug = normalizedConfig.debug_mode;
    
    // 调试输出
    if (isDebug) {
        console.log('文本盲水印JS初始化');
        console.log('配置:', normalizedConfig);
        console.log('用户状态:', {
            isLoggedIn: wxstbw_isUserLoggedIn,
            userId: wxstbw_current_user_id,
            userRoles: wxstbw_current_user_roles,
            isArticlePage: wxstbw_isArticlePage
        });
    }

    // 检查是否启用
    if (!normalizedConfig.enable) {
        if (isDebug) console.log('插件未启用，跳过处理');
        return;
    }
    
    // 检查用户组控制
    if (normalizedConfig.user_group_enable) {
        // 获取用户角色信息（从WordPress传递）
        const userRoles = Array.isArray(wxstbw_current_user_roles) ? wxstbw_current_user_roles : [];
        const isGuest = !wxstbw_isUserLoggedIn;
        
        let shouldProcess = true;
        
        switch (normalizedConfig.user_group_type) {
            case 'wordpress':
                // WordPress用户组检测
                if (isGuest) {
                    // 游客处理 - 通常默认处理，但也可以根据配置调整
                    shouldProcess = true;
                    if (isDebug) console.log('用户组控制: 游客用户，默认处理水印');
                } else if (normalizedConfig.wordpress_user_roles.length > 0) {
                    // 检查用户角色是否在允许的列表中
                    shouldProcess = false;
                    for (const role of userRoles) {
                        if (normalizedConfig.wordpress_user_roles.includes(role)) {
                            shouldProcess = true;
                            break;
                        }
                    }
                    if (isDebug) {
                        console.log(`用户组控制: WordPress角色检查 - 用户角色: ${userRoles.join(', ')}`);
                        console.log(`用户组控制: 插入水印的角色: ${normalizedConfig.wordpress_user_roles.join(', ')}`);
                        console.log(`用户组控制: 是否允许插入水印: ${shouldProcess}`);
                    }
                } else {
                    // 如果没有配置允许的角色，则默认处理所有用户
                    shouldProcess = true;
                    if (isDebug) console.log('用户组控制: 未配置允许的角色，默认处理所有用户');
                }
                break;
                
            case 'custom':
                // 自定义用户组检测
                // 这里不能直接调用PHP函数，所以我们依赖于后端传递的信息
                // 复杂的权限检查应该在PHP端完成
                shouldProcess = true;
                if (isDebug) {
                    console.log('用户组控制: 自定义用户组模式 - 复杂权限检查在PHP端完成');
                }
                break;
        }
        
        if (!shouldProcess) {
            if (isDebug) {
                console.log('用户组控制 - 跳过当前用户的水印处理');
                console.log('用户角色:', userRoles);
                console.log('允许的角色:', normalizedConfig.wordpress_user_roles);
            }
            return;
        }
    }

    // 检查运行模式
    if (normalizedConfig.run_mode === 'hybrid' && wxstbw_isUserLoggedIn) {
        if (isDebug) console.log('混合模式 - 登录用户，跳过JS处理');
        return;
    }

    if (normalizedConfig.run_mode === 'dynamic') {
        if (isDebug) console.log('动态模式 - 纯PHP处理，跳过JS');
        return;
    }

    // 爬虫过滤
    const userAgent = navigator.userAgent.toLowerCase();
    const botUAs = normalizedConfig.bot_ua
        .map(bot => bot.toString().trim().toLowerCase())
        .filter(bot => bot);
    
    const isBot = botUAs.some(bot => userAgent.includes(bot));
    if (isBot) {
        if (isDebug) console.log('检测到爬虫，跳过处理');
        return;
    }

    // 变体选择器定义
    const VARIATION_SELECTOR_START = 0xFE00;
    const VARIATION_SELECTOR_SUPPLEMENT_START = 0xE0100;

    // 字节转换为变体选择器字符
    function byteToChar(byte) {
        if (!Number.isInteger(byte) || byte < 0 || byte > 255) return '';
        
        if (byte < 16) {
            return String.fromCodePoint(VARIATION_SELECTOR_START + byte);
        } else {
            return String.fromCodePoint(VARIATION_SELECTOR_SUPPLEMENT_START + (byte - 16));
        }
    }

    // 获取IP地址
    let pageIP = 'unknown';
    async function fetchIP() {
        try {
            // 获取当前页面的基础URL
            const currentUrl = window.location.href;
            
            // 解析当前URL
            const url = new URL(currentUrl);
            
            // 设置查询参数
            url.searchParams.set('wxstbw_query', 'getip');
            
            // 移除hash部分，避免问题
            url.hash = '';
            
            if (isDebug) {
                console.log('请求IP的URL:', url.toString());
            }
            
            const response = await fetch(url.toString(), {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin'
            });
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const data = await response.json();
            if (data.success) {
                pageIP = data.ip;
                if (isDebug) {
                    console.log('获取到IP地址:', pageIP);
                }
            } else {
                if (isDebug) {
                    console.warn('获取IP失败:', data.error || '未知错误');
                }
            }
        } catch (error) {
            if (isDebug) {
                console.error('获取IP失败:', error);
                // 使用备用方法获取IP
                pageIP = 'unknown';
            }
        }
    }

    // 生成水印内容
    function generateWatermarkContent() {
        const parts = [];
        
        if (normalizedConfig.watermark_content.include_ip) {
            parts.push(`IP:${pageIP}`);
        }
        
        if (normalizedConfig.watermark_content.include_user) {
            parts.push(`USER:${wxstbw_isUserLoggedIn ? wxstbw_current_user_id : 'guest'}`);
        }
        
        if (normalizedConfig.watermark_content.include_time) {
            // 获取WordPress时区设置（这里简化处理，实际应该从服务器获取时区设置）
            const now = new Date();
            const timeStr = now.getFullYear() + '-' + 
                          String(now.getMonth() + 1).padStart(2, '0') + '-' + 
                          String(now.getDate()).padStart(2, '0') + ' ' + 
                          String(now.getHours()).padStart(2, '0') + ':' + 
                          String(now.getMinutes()).padStart(2, '0') + ':' + 
                          String(now.getSeconds()).padStart(2, '0');
            parts.push(`TIME:${timeStr}`);
        }
        
        if (normalizedConfig.watermark_content.include_custom && normalizedConfig.watermark_content.custom_text) {
            parts.push(normalizedConfig.watermark_content.custom_text);
        }
        
        return parts.join('|');
    }

    // 生成水印字符串
    async function generateWatermark() {
        if (normalizedConfig.watermark_content.include_ip) {
            await fetchIP();
        }
        const rawContent = generateWatermarkContent();
        
        if (isDebug) {
            console.log('水印内容:', rawContent);
            return `[JS-WATERMARK:${rawContent}]`;
        }
        
        // 将字符串转换为字节数组
        const encoder = new TextEncoder();
        const bytes = encoder.encode(rawContent);
        
        // 将每个字节转换为变体选择器字符
        let watermark = '';
        for (let i = 0; i < bytes.length; i++) {
            watermark += byteToChar(bytes[i]);
        }
        
        return watermark;
    }

    // 计算随机插入次数
    function calcRandomCount(textLength) {
        if (normalizedConfig.random.count_type == 1) {
            const customCount = normalizedConfig.random.custom_count || 1;
            return Math.max(1, customCount);
        } else {
            const ratio = normalizedConfig.random.word_based_ratio || 400;
            return Math.max(1, Math.floor(textLength / ratio));
        }
    }

    // 检查节点是否在排除的标签内
    function isInsideExcludedTag(node) {
        let parent = node.parentElement;
        while (parent) {
            const tagName = parent.tagName.toLowerCase();
            // 排除code标签及其内容
            if (tagName === 'code') {
                return true;
            }
            parent = parent.parentElement;
        }
        return false;
    }

    // URL正则表达式
    const URL_PATTERN = /(https?:\/\/[^\s"<>]+)/gi;

    // 处理文本中的URL部分 - 分割文本，只对非URL部分处理
    function processTextWithUrlFilter(text, watermark) {
        if (!text || !watermark) {
            return text;
        }
        
        // 使用split方法分割文本，同时保留URL部分
        const parts = text.split(URL_PATTERN);
        
        if (parts.length === 1) {
            // 没有URL，直接处理整个文本
            return processParagraph(text, watermark);
        }
        
        let result = '';
        for (let i = 0; i < parts.length; i++) {
            if (i % 2 === 0) {
                // 偶数索引：非URL部分，需要处理
                result += processParagraph(parts[i], watermark);
            } else {
                // 奇数索引：URL部分，直接保留，不处理
                result += parts[i];
            }
        }
        
        return result;
    }

    // 处理单个段落文本
    function processParagraph(text, watermark) {
        const minLength = normalizedConfig.min_paragraph_length || 20;
        const textLength = text.length;
        
        if (textLength < minLength || !watermark) {
            return text;
        }
        
        const method = normalizedConfig.insert_method || 2;
        switch (method) {
            case 1: // 段落末尾插入
                return text + watermark;
                
            case 2: // 随机位置插入
                const insertCount = calcRandomCount(textLength);
                const positions = [];
                
                // 避免插入次数超过文本长度
                const actualInsertCount = Math.min(insertCount, textLength - 1);
                
                for (let i = 0; i < actualInsertCount; i++) {
                    let pos;
                    do {
                        pos = Math.floor(Math.random() * (textLength - 1)) + 1;
                    } while (positions.includes(pos));
                    positions.push(pos);
                }
                positions.sort((a, b) => a - b);
                
                let result = "";
                let lastPos = 0;
                for (const pos of positions) {
                    result += text.substring(lastPos, pos);
                    result += watermark;
                    lastPos = pos;
                }
                result += text.substring(lastPos);
                return result;
                
            case 3: // 固定字数插入
                const interval = Math.max(5, normalizedConfig.fixed.interval || 20);
                
                let fixedResult = "";
                for (let i = 0; i < textLength; i++) {
                    fixedResult += text[i];
                    if ((i + 1) % interval === 0 && i < textLength - 1) {
                        fixedResult += watermark;
                    }
                }
                return fixedResult;
                
            default:
                return text;
        }
    }

    // 处理单个文本节点
    function processTextNode(node, watermark) {
        // 检查是否在排除的标签内（如code标签）
        if (isInsideExcludedTag(node)) {
            return;
        }
        
        const originalText = node.nodeValue;
        
        // 使用带有URL过滤的处理函数
        const processedText = processTextWithUrlFilter(originalText, watermark);
        
        if (processedText !== originalText) {
            node.nodeValue = processedText;
        }
    }

    // 处理元素
    async function processElement(element, watermark) {
        // 创建TreeWalker遍历文本节点
        const walker = document.createTreeWalker(
            element,
            NodeFilter.SHOW_TEXT,
            {
                acceptNode: function(node) {
                    // 跳过空的文本节点
                    if (!node.nodeValue || !node.nodeValue.trim()) {
                        return NodeFilter.FILTER_REJECT;
                    }
                    return NodeFilter.FILTER_ACCEPT;
                }
            }
        );
        
        // 处理所有文本节点
        let node;
        const nodesToProcess = [];
        while (node = walker.nextNode()) {
            nodesToProcess.push(node);
        }
        
        // 批量处理文本节点
        for (const textNode of nodesToProcess) {
            processTextNode(textNode, watermark);
        }
    }

    // 初始化处理
    async function initializeProcessing() {
        if (isDebug) console.log('开始处理水印');
        
        // 生成水印
        const watermark = await generateWatermark();
        if (!watermark || watermark.length === 0) {
            if (isDebug) console.log('水印为空，跳过处理');
            return;
        }
        
        if (isDebug) console.log('水印长度:', watermark.length);
        
        // 获取要处理的标签
        const tags = normalizedConfig.htmlTags;
        
        if (isDebug) console.log('处理的标签:', tags);
        
        // 1. 首先处理文章内容
        if (wxstbw_isArticlePage || normalizedConfig.global_force_article) {
            if (isDebug) console.log('处理文章页面内容');
            
            // 处理所有配置的标签
            for (const tag of tags) {
                const elements = document.querySelectorAll(tag);
                if (isDebug && elements.length > 0) {
                    console.log(`找到 ${elements.length} 个 <${tag}> 元素`);
                }
                
                for (const element of elements) {
                    await processElement(element, watermark);
                }
            }
        }
        
        // 2. 处理全局选择器
        if (normalizedConfig.jsGlobalEnable) {
            if (isDebug) console.log('处理全局选择器');
            
            const selectors = [];
            
            // 处理类选择器
            if (normalizedConfig.jsClassSelectors) {
                const classSelectors = normalizedConfig.jsClassSelectors
                    .split(',')
                    .map(s => s.trim())
                    .filter(s => s)
                    .map(s => s.startsWith('.') ? s : '.' + s);
                
                selectors.push(...classSelectors);
            }
            
            // 处理ID选择器
            if (normalizedConfig.jsIdSelectors) {
                const idSelectors = normalizedConfig.jsIdSelectors
                    .split(',')
                    .map(s => s.trim())
                    .filter(s => s)
                    .map(s => s.startsWith('#') ? s : '#' + s);
                
                selectors.push(...idSelectors);
            }
            
            if (selectors.length > 0) {
                for (const selector of selectors) {
                    try {
                        const elements = document.querySelectorAll(selector);
                        if (isDebug && elements.length > 0) {
                            console.log(`选择器 ${selector} 找到 ${elements.length} 个元素`);
                        }
                        
                        for (const element of elements) {
                            // 对于全局选择器，只处理配置的标签
                            const tagName = element.tagName.toLowerCase();
                            if (tags.includes(tagName)) {
                                await processElement(element, watermark);
                            }
                        }
                    } catch (error) {
                        if (isDebug) console.error(`选择器错误 ${selector}:`, error);
                    }
                }
            }
        }
        
        if (isDebug) console.log('水印处理完成');
    }

    // 启动处理
    initializeProcessing().catch(error => {
        if (isDebug) console.error('水印处理失败:', error);
    });
});

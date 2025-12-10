document.addEventListener('DOMContentLoaded', function() {
    // 页面状态变量
    const wxs_isUserLoggedIn = window.wxs_isUserLoggedIn || false;
    const wxs_current_user_id = window.wxs_current_user_id || false;
    const wxs_current_user_roles = window.wxs_current_user_roles || [];
    const wxs_isArticlePage = window.wxs_isArticlePage || false;

    // 加载配置
    const config = window.wxsWatermarkConfig || {};
    
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
        }
    };
    
    const isDebug = normalizedConfig.debug_mode;
    
    // 调试输出
    if (isDebug) {
        console.log('文本盲水印JS初始化');
        console.log('配置:', normalizedConfig);
        console.log('用户状态:', {
            isLoggedIn: wxs_isUserLoggedIn,
            userId: wxs_current_user_id,
            userRoles: wxs_current_user_roles,
            isArticlePage: wxs_isArticlePage
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
        const userRoles = Array.isArray(wxs_current_user_roles) ? wxs_current_user_roles : [];
        const isGuest = !wxs_isUserLoggedIn;
        
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
    if (normalizedConfig.run_mode === 'hybrid' && wxs_isUserLoggedIn) {
        if (isDebug) console.log('混合模式 - 登录用户，跳过JS处理');
        return;
    }

    if (normalizedConfig.run_mode === 'dynamic') {
        if (isDebug) console.log('动态模式 - 纯PHP处理，跳过JS');
        return;
    }

    // 爬虫过滤
    const userAgent = navigator.userAgent.toLowerCase();
    const botUAs = (Array.isArray(normalizedConfig.bot_ua) ? normalizedConfig.bot_ua : [])
        .map(bot => bot.toString().trim().toLowerCase())
        .filter(bot => bot);
    
    const isBot = botUAs.some(bot => userAgent.includes(bot));
    if (isBot) {
        if (isDebug) console.log('检测到爬虫，跳过处理');
        return;
    }

    // 生成水印字符
    function byteToChar(byte) {
        if (!Number.isInteger(byte) || byte < 0 || byte > 255) return '';
        
        if (byte < 16) {
            return String.fromCodePoint(0xFE00 + byte);
        } else {
            return String.fromCodePoint(0xE0100 + (byte - 16));
        }
    }

    // 获取IP地址
    let pageIP = 'unknown';
    async function fetchIP() {
        try {
            const response = await fetch(normalizedConfig.ip_endpoint);
            const data = await response.json();
            if (data.success) {
                pageIP = data.ip;
            }
        } catch (error) {
            if (isDebug) console.error('获取IP失败:', error);
        }
    }

    // 生成水印内容
    function generateWatermarkContent() {
        const parts = [];
        
        if (normalizedConfig.watermark_content.include_ip) {
            parts.push(`IP:${pageIP}`);
        }
        
        if (normalizedConfig.watermark_content.include_user) {
            parts.push(`USER:${wxs_isUserLoggedIn ? wxs_current_user_id : 'guest'}`);
        }
        
        if (normalizedConfig.watermark_content.include_time) {
            const now = new Date();
            const timeStr = `${now.getFullYear()}-${String(now.getMonth()+1).padStart(2,'0')}-${String(now.getDate()).padStart(2,'0')} ${String(now.getHours()).padStart(2,'0')}:${String(now.getMinutes()).padStart(2,'0')}:${String(now.getSeconds()).padStart(2,'0')}`;
            parts.push(`TIME:${timeStr}`);
        }
        
        if (normalizedConfig.watermark_content.include_custom && normalizedConfig.watermark_content.custom_text) {
            parts.push(normalizedConfig.watermark_content.custom_text);
        }
        
        return parts.join('|');
    }

    // 生成水印字符串
    async function generateWatermark() {
        await fetchIP();
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

    // 处理文本节点
    async function processTextNode(node, watermark) {
        const originalText = node.textContent;
        
        // 检查文本长度
        if (originalText.length < normalizedConfig.min_paragraph_length) {
            return originalText;
        }
        
        let processedText = originalText;
        
        switch (normalizedConfig.insert_method) {
            case 1: // 末尾插入
                processedText = originalText + watermark;
                break;
                
            case 2: // 随机插入
                const textLength = originalText.length;
                let insertCount;
                
                if (normalizedConfig.random.count_type === 1) {
                    insertCount = Math.max(1, normalizedConfig.random.custom_count);
                } else {
                    insertCount = Math.max(1, Math.floor(textLength / normalizedConfig.random.word_based_ratio));
                }
                
                // 确保插入次数不超过文本长度
                insertCount = Math.min(insertCount, Math.max(1, textLength - 1));
                
                // 生成插入位置
                const positions = [];
                for (let i = 0; i < insertCount; i++) {
                    let pos;
                    do {
                        pos = Math.floor(Math.random() * (textLength - 1)) + 1;
                    } while (positions.includes(pos) && positions.length < textLength);
                    
                    if (!positions.includes(pos)) {
                        positions.push(pos);
                    }
                }
                
                positions.sort((a, b) => a - b);
                
                // 插入水印
                let result = '';
                let lastPos = 0;
                for (const pos of positions) {
                    result += originalText.substring(lastPos, pos);
                    result += watermark;
                    lastPos = pos;
                }
                result += originalText.substring(lastPos);
                processedText = result;
                break;
                
            case 3: // 固定间隔插入
                const interval = Math.max(5, normalizedConfig.fixed.interval);
                let fixedResult = '';
                
                for (let i = 0; i < originalText.length; i++) {
                    fixedResult += originalText[i];
                    if ((i + 1) % interval === 0 && i < originalText.length - 1) {
                        fixedResult += watermark;
                    }
                }
                processedText = fixedResult;
                break;
        }
        
        return processedText;
    }

    // 处理元素
    async function processElement(element, watermark, tags) {
        // 检查是否是配置的标签
        const tagName = element.tagName.toLowerCase();
        const shouldProcess = tags.includes(tagName);
        
        if (!shouldProcess && !normalizedConfig.jsGlobalEnable) {
            return;
        }
        
        // 创建TreeWalker遍历文本节点
        const walker = document.createTreeWalker(
            element,
            NodeFilter.SHOW_TEXT,
            {
                acceptNode: function(node) {
                    // 跳过空的文本节点
                    if (!node.textContent.trim()) {
                        return NodeFilter.FILTER_REJECT;
                    }
                    return NodeFilter.FILTER_ACCEPT;
                }
            }
        );
        
        const textNodes = [];
        let node;
        while (node = walker.nextNode()) {
            textNodes.push(node);
        }
        
        // 处理文本节点
        for (const textNode of textNodes) {
            const processedText = await processTextNode(textNode, watermark);
            if (processedText !== textNode.textContent) {
                textNode.textContent = processedText;
            }
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
        
        // 获取要处理的标签
        const tags = Array.isArray(normalizedConfig.htmlTags) 
            ? normalizedConfig.htmlTags 
            : ['p', 'li'];
        
        if (isDebug) console.log('处理的标签:', tags);
        
        // 1. 首先处理文章内容
        if (wxs_isArticlePage) {
            if (isDebug) console.log('处理文章页面内容');
            
            const articleSelectors = [
                '.article-content',
                '.post-content',
                '.entry-content',
                '#content .article',
                '.post'
            ];
            
            let articleContainer = null;
            for (const selector of articleSelectors) {
                articleContainer = document.querySelector(selector);
                if (articleContainer) break;
            }
            
            if (articleContainer) {
                // 处理配置的标签
                for (const tag of tags) {
                    const elements = articleContainer.querySelectorAll(tag);
                    if (isDebug) console.log(`找到 ${elements.length} 个 <${tag}> 元素`);
                    
                    for (const element of elements) {
                        await processElement(element, watermark, tags);
                    }
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
                        if (isDebug) console.log(`选择器 ${selector} 找到 ${elements.length} 个元素`);
                        
                        for (const element of elements) {
                            await processElement(element, watermark, tags);
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

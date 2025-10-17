document.addEventListener('DOMContentLoaded', function() {
    // 页面状态变量
    const wxs_isUserLoggedIn = window.wxs_isUserLoggedIn || false;
    const wxs_current_user_id = window.wxs_current_user_id || false;
    const wxs_isArticlePage = window.wxs_isArticlePage || false;

    // 加载配置
    const config = window.wxsWatermarkConfig || {};
    

    // 配置规范化（统一处理布尔值类型，避免字符串"0"/"1"误判）
    const normalizedConfig = {
        ...config,
        // 将字符串"0"/"1"转为布尔值
        enable: !!Number(config.enable),
        jsGlobalEnable: !!Number(config.jsGlobalEnable),
        global_force_article: !!Number(config.global_force_article),
        debug_mode: !!Number(config.debug_mode),
        // 水印内容的布尔值处理
        watermark_content: {
            ...config.watermark_content,
            include_ip: !!Number(config.watermark_content?.include_ip),
            include_user: !!Number(config.watermark_content?.include_user),
            include_time: !!Number(config.watermark_content?.include_time),
            include_custom: !!Number(config.watermark_content?.include_custom),
        },
        // 其他数值型配置转为数字
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


    // 仅在调试模式下输出初始化信息
    if (isDebug) {
        console.log('文本盲水印JS初始化 - 纯JS模式');
        console.log('用户登录状态:', wxs_isUserLoggedIn);
        console.log('当前用户ID:', wxs_current_user_id);
        console.log('是否为文章页面:', wxs_isArticlePage);
        console.log('原始配置信息:', config);
        console.log('规范化后配置信息:', normalizedConfig);
    }

    // 判断插件是否启用
    const isEnabled = normalizedConfig.enable;
    // 判断JS全局模式是否启用
    const isGlobalEnabled = normalizedConfig.jsGlobalEnable;

    // 仅在调试模式下输出启用状态
    if (isDebug) {
        console.log('是否启用水印:', isEnabled);
        console.log('是否启用全局处理:', isGlobalEnabled);
        console.log('文章页是否强制启用全局选择器:', normalizedConfig.global_force_article);
    }

    if (!isEnabled) {
        return;
    }

    // 不是文章页面且未启用全局处理时才跳过
    if (!wxs_isArticlePage && !isGlobalEnabled) {
        if (isDebug) {
            console.log('当前不是文章页面且未启用全局处理，不处理水印');
        }
        return;
    }

    // 混合模式下，登录用户不执行JS水印处理
    if (normalizedConfig.run_mode === 'hybrid' && wxs_isUserLoggedIn) {
        if (isDebug) {
            console.log('混合模式 - 登录用户，不执行JS水印处理');
        }
        return;
    }

    // 爬虫过滤
    const userAgent = navigator.userAgent.toLowerCase();
    const botUAs = (normalizedConfig.bot_ua || []).map(bot => bot.trim().toLowerCase());
    const isBot = botUAs.some(bot => userAgent.includes(bot));
    if (isBot) {
        if (isDebug) {
            console.log('检测到爬虫，不插入水印');
        }
        return;
    }

    // 获取HTML标签配置
    const htmlTags = normalizedConfig.htmlTags || ['p', 'li'];

    // 目标容器
    const articleContainer = document.querySelector('.article-content') 
        || document.querySelector('.post-content')
        || document.querySelector('#content')
        || document.querySelector('.entry-content')
        || document.body;
    if (!articleContainer) {
        if (isDebug) {
            console.error('未找到文章内容容器，无法插入水印');
        }
        return;
    }

    // IP获取逻辑
    let pageIP = 'unknown';
    let ipReady = false;
    let pendingTasks = [];

    async function fetchPageIP() {
        if (isDebug) {
            console.log('开始获取IP...');
        }
        try {
            const res = await fetch(normalizedConfig.ip_endpoint);
            if (!res.ok) throw new Error(`HTTP状态码: ${res.status}`);
            const data = await res.json();
            pageIP = data.success ? data.ip : 'unknown-ip';
            if (isDebug) {
                console.log('IP获取成功:', pageIP);
            }
        } catch (e) {
            if (isDebug) {
                console.error('IP获取失败:', e);
            }
            pageIP = 'unknown-ip';
        } finally {
            ipReady = true;
            pendingTasks.forEach(task => task());
            pendingTasks = [];
        }
    }
    fetchPageIP();

    // 水印信息生成器
    class WatermarkInfo {
        constructor(contentConfig) {
            this.contentConfig = contentConfig;
            if (isDebug) {
                console.log('水印内容配置:', this.contentConfig);
            }
        }
        
        getIP() { return pageIP; }
        
        getUser() { 
            return wxs_isUserLoggedIn ? (wxs_current_user_id || 'user') : 'guest';
        }
        
        formatTime() {
            const d = new Date();
            return `${d.getFullYear()}-${(d.getMonth()+1).toString().padStart(2, '0')}-${d.getDate().toString().padStart(2, '0')} ${d.getHours().toString().padStart(2, '0')}:${d.getMinutes().toString().padStart(2, '0')}:${d.getSeconds().toString().padStart(2, '0')}`;
        }
        
        async generateRaw() {
            const parts = [];
            const { include_ip, include_user, include_time, include_custom, custom_text } = this.contentConfig;
            
            if (include_ip) {
                if (isDebug) {
                    console.log('include_ip为true，添加IP信息');
                }
                parts.push(`IP:${this.getIP()}`);
            }
            
            if (include_user) {
                if (isDebug) {
                    console.log('include_user为true，添加用户信息');
                }
                parts.push(`USER:${this.getUser()}`);
            }
            
            if (include_time) {
                if (isDebug) {
                    console.log('include_time为true，添加时间信息');
                }
                parts.push(`TIME:${this.formatTime()}`);
            }
            
            if (include_custom && custom_text) {
                if (isDebug) {
                    console.log('include_custom为true，添加自定义文本');
                }
                parts.push(custom_text);
            }
            
            const raw = parts.join('|');
            if (isDebug) {
                console.log('最终生成的水印原始内容:', raw);
            }
            return raw;
        }
    }

    // 水印处理器
    class WatermarkProcessor {
        constructor(config) {
            this.config = config;
            this.info = new WatermarkInfo(config.watermark_content || {});
            this.minLength = config.min_paragraph_length || 20;
            this.insertMethod = config.insert_method || 2;
            this.random = {
                count_type: config.random?.count_type || 2,
                custom_count: config.random?.custom_count || 1,
                word_based_ratio: config.random?.word_based_ratio || 400
            };
            this.fixed = { interval: config.fixed?.interval || 20 };
            this.isDebug = config.debug_mode;
            this.maxPositionAttempts = 100;
            
            if (isDebug) {
                console.log('水印处理器初始化完成');
            }
        }

        byteToChar(byte) {
            if (!Number.isInteger(byte) || byte < 0 || byte > 255) return '';
            return byte < 16 
                ? String.fromCodePoint(0xFE00 + byte) 
                : String.fromCodePoint(0xE0100 + (byte - 16));
        }

        async generateWatermark() {
            const raw = await this.info.generateRaw();
            if (this.isDebug) {
                const debugMark = `[水印调试JS模式：${raw}]`;
                console.log('生成调试水印:', debugMark);
                return debugMark;
            }
            return Array.from(new TextEncoder().encode(raw)).map(b => this.byteToChar(b)).join('');
        }

        getInsertCount(textLength) {
            let count = this.random.count_type === 1 
                ? Math.max(1, this.random.custom_count) 
                : Math.max(1, Math.floor(textLength / this.random.word_based_ratio));
            
            // 修复：确保至少能插入1次水印
            const maxPossibleCount = Math.max(1, Math.floor(textLength / 10)); // 降低分母，确保至少1次
            count = Math.min(count, maxPossibleCount);
            
            if (isDebug) {
                console.log(`插入次数计算: 文本长度=${textLength}, 理论计算=${count}, 最大可能=${maxPossibleCount}`);
            }
            return count;
        }

        getRandomPositions(textLength) {
            const positions = [];
            const count = this.getInsertCount(textLength);
            
            // 如果计算出的插入次数为0，强制插入1次
            const actualCount = count <= 0 ? 1 : count;
            
            const safeStart = 5;
            const safeEnd = Math.max(safeStart + 1, textLength - 5); // 确保有足够的空间
            
            if (safeStart >= safeEnd) {
                // 如果文本太短，就在中间插入
                return [Math.floor(textLength / 2)];
            }
            
            if (isDebug) {
                console.log(`生成随机位置: 需求=${actualCount}, 文本长度=${textLength}, 范围=[${safeStart}, ${safeEnd}]`);
            }
            
            for (let i = 0; i < actualCount; i++) {
                let pos;
                let attempts = 0;
                let foundValidPosition = false;
                
                do {
                    pos = Math.floor(Math.random() * (safeEnd - safeStart)) + safeStart;
                    pos = Math.max(safeStart, Math.min(pos, safeEnd));
                    attempts++;
                    
                    if (attempts >= this.maxPositionAttempts) {
                        // 如果尝试次数过多，使用备选位置
                        pos = safeStart + Math.floor(i * (safeEnd - safeStart) / actualCount);
                        foundValidPosition = true;
                        break;
                    }
                    
                    // 降低最小间隔要求，从30降到10，适应短文本
                    if (!positions.some(p => Math.abs(p - pos) < 10)) {
                        foundValidPosition = true;
                        break;
                    }
                } while (true);
                
                if (foundValidPosition) {
                    positions.push(pos);
                    if (isDebug) {
                        console.log(`生成位置 ${i+1}: ${pos} (尝试${attempts}次)`);
                    }
                }
            }
            
            return positions.sort((a, b) => a - b);
        }

        async processText(text) {
            if (typeof text !== 'string') {
                if (isDebug) {
                    console.error('无效文本类型，跳过处理');
                }
                return text;
            }
            
            const textLength = text.length;
            if (isDebug) {
                console.log(`处理文本: 长度=${textLength}, 最小要求=${this.minLength}`);
            }
            
            // 修复：即使文本长度小于最小值，也尝试插入水印（但只在文本足够长时）
            if (textLength < 5) {
                return text; // 太短的文本不处理
            }
            
            let watermark;
            try {
                watermark = await this.generateWatermark();
                if (!watermark) return text;
                
                if (isDebug) {
                    console.log(`水印长度: ${watermark.length}, 内容: ${watermark}`);
                }
            } catch (e) {
                if (isDebug) {
                    console.error('水印生成失败:', e);
                }
                return text;
            }
        
            // 根据文本长度选择插入策略
            if (textLength < this.minLength) {
                // 短文本处理：只在末尾插入
                if (isDebug) {
                    console.log('短文本，使用末尾插入策略');
                }
                return text + watermark;
            }
        
            switch (this.insertMethod) {
                case 1: 
                    return text + watermark;
                    
                case 2: {
                    const positions = this.getRandomPositions(textLength);
                    if (isDebug) {
                        console.log(`随机插入位置: ${JSON.stringify(positions)}`);
                    }
                    
                    // 修复：如果没有生成位置，使用末尾插入
                    if (positions.length === 0) {
                        if (isDebug) {
                            console.log('未生成插入位置，使用末尾插入');
                        }
                        return text + watermark;
                    }
                    
                    let result = '';
                    let lastPos = 0;
                    
                    for (const pos of positions) {
                        const currentPos = Math.max(lastPos, Math.min(pos, textLength));
                        result += text.substring(lastPos, currentPos);
                        result += watermark;
                        lastPos = currentPos;
                    }
                    
                    result += text.substring(lastPos);
                    return result;
                }
                
                case 3: {
                    let result = '';
                    const interval = Math.max(5, this.fixed.interval); // 确保间隔不要太长
                    for (let i = 0; i < textLength; i++) {
                        result += text[i];
                        if ((i + 1) % interval === 0 && i < textLength - 1) {
                            result += watermark;
                        }
                    }
                    return result;
                }
                
                default: 
                    return text;
            }
        }
    }

    // 使用规范化后的配置初始化处理器
    const processor = new WatermarkProcessor(normalizedConfig);

    async function processTextNode(node) {
        if (node.nodeType !== 3 || !node.textContent.trim()) return;
        if (!ipReady) await new Promise(resolve => pendingTasks.push(resolve));
        
        const original = node.textContent;
        const processed = await processor.processText(original);
        if (processed !== original) {
            node.textContent = processed;
        }
    }

    async function processTag(tag, deep = true) {
        if (!ipReady) await new Promise(resolve => pendingTasks.push(resolve));
        
        // 如果是深度处理，递归处理所有文本节点
        if (deep) {
            const walker = document.createTreeWalker(
                tag, 
                NodeFilter.SHOW_TEXT, 
                null, 
                false
            );
            
            const textNodes = [];
            let node;
            while (node = walker.nextNode()) {
                textNodes.push(node);
            }
            
            await Promise.all(textNodes.map(async textNode => {
                const original = textNode.textContent;
                const processed = await processor.processText(original);
                if (processed !== original) {
                    textNode.textContent = processed;
                }
            }));
            
            return;
        }
        
        // 非深度处理，只处理直接子文本节点
        const fragment = document.createDocumentFragment();
        const promises = [];
        tag.childNodes.forEach(child => {
            if (child.nodeType === 3) {
                promises.push((async () => {
                    const originalText = child.textContent;
                    const processedText = await processor.processText(originalText);
                    fragment.appendChild(document.createTextNode(processedText || originalText));
                })());
            } else {
                fragment.appendChild(child.cloneNode(true));
            }
        });
        await Promise.all(promises);
        tag.innerHTML = '';
        tag.appendChild(fragment);
    }

    // 解析选择器
    function parseSelector(selector) {
        if (selector.startsWith('[') && selector.endsWith(']')) {
            const content = selector.slice(1, -1);
            const parts = content.split('&');
            
            if (parts.length === 2) {
                const tag = parts[0];
                const identifier = parts[1];
                
                if (identifier.startsWith('.')) {
                    return {
                        selector: `${tag}${identifier}`,
                        deep: false
                    };
                } else if (identifier.startsWith('#')) {
                    return {
                        selector: `${tag}${identifier}`,
                        deep: false
                    };
                }
            }
        }
        
        // 默认处理普通选择器
        return {
            selector: selector,
            deep: true
        };
    }

    // 处理选择器匹配的元素
    async function processSelectors(selectors) {
        if (!selectors || !selectors.trim()) return [];
        
        const selectorList = selectors.split(',').map(s => s.trim()).filter(s => s);
        const elements = [];
        
        for (const selector of selectorList) {
            const { selector: parsedSelector, deep } = parseSelector(selector);
            try {
                const foundElements = document.querySelectorAll(parsedSelector);
                foundElements.forEach(el => {
                    elements.push({ element: el, deep });
                });
            } catch (e) {
                if (isDebug) {
                    console.error(`无效的选择器: ${selector}`, e);
                }
            }
        }
        
        return elements;
    }

    // 处理文章内容
    async function processArticleContent() {
        if (!wxs_isArticlePage) return;
        
        const tags = articleContainer.querySelectorAll(htmlTags.join(','));
        if (isDebug) {
            console.log(`找到${tags.length}个文章内容标签`);
        }
        
        const batchSize = 5;
        for (let i = 0; i < tags.length; i += batchSize) {
            const batch = Array.from(tags).slice(i, i + batchSize);
            await Promise.all(batch.map(tag => processTag(tag)));
            await new Promise(resolve => setTimeout(resolve, 50));
        }
    }

    // 处理全局选择器
    async function processGlobalSelectors() {
        if (!isGlobalEnabled) return;
        
        try {
            // 获取并规范化所有选择器
            const selectors = [];
            
            // 处理class选择器
            if (normalizedConfig.jsClassSelectors) {
                selectors.push(...normalizedConfig.jsClassSelectors.split(',')
                    .map(s => s.trim())
                    .filter(s => s)
                    .map(s => s.startsWith('.') ? s : `.${s}`));
            }
            
            // 处理id选择器
            if (normalizedConfig.jsIdSelectors) {
                selectors.push(...normalizedConfig.jsIdSelectors.split(',')
                    .map(s => s.trim())
                    .filter(s => s)
                    .map(s => s.startsWith('#') ? s : `#${s}`));
            }
    
            if (selectors.length === 0) {
                if (isDebug) console.log('没有可用的全局选择器');
                return;
            }
    
            if (isDebug) console.log('处理全局选择器:', selectors);
    
            // 单独处理每个选择器
            for (const selector of selectors) {
                try {
                    const elements = document.querySelectorAll(selector);
                    if (isDebug) console.log(`选择器 "${selector}" 找到 ${elements.length} 个元素`);
    
                    // 批量处理元素
                    const batchSize = 5;
                    for (let i = 0; i < elements.length; i += batchSize) {
                        const batch = Array.from(elements).slice(i, i + batchSize);
                        await Promise.all(batch.map(el => {
                            // 容器元素深度处理，其他元素不深度处理
                            const isContainer = selector.includes('.wp-posts-content') || 
                                              selector.includes('#main-content');
                            return processTag(el, isContainer);
                        }));
                        await new Promise(resolve => setTimeout(resolve, 50));
                    }
                } catch (e) {
                    if (isDebug) console.error(`选择器 ${selector} 处理失败:`, e);
                }
            }
        } catch (e) {
            if (isDebug) console.error('全局选择器处理错误:', e);
        }
    }

    // 初始化处理
    async function initProcessing() {
        // 混合模式判断
        if (normalizedConfig.run_mode === 'hybrid' && wxs_isUserLoggedIn) {
            if (isDebug) console.log('混合模式 - 登录用户，跳过JS处理');
            return;
        }
    
        if (!ipReady) await new Promise(resolve => pendingTasks.push(resolve));
    
        // 文章页面处理
        if (wxs_isArticlePage) {
            const articleContent = document.querySelector('.article-content') || document.body;
            const tags = articleContent.querySelectorAll(htmlTags.join(','));
            if (isDebug) console.log(`文章页找到 ${tags.length} 个 ${htmlTags} 标签`);
            
            const batchSize = 5;
            for (let i = 0; i < tags.length; i += batchSize) {
                const batch = Array.from(tags).slice(i, i + batchSize);
                await Promise.all(batch.map(tag => processTag(tag, false)));
                await new Promise(resolve => setTimeout(resolve, 50));
            }
        }
    
        // 全局选择器处理（非文章页面或强制启用时）
        if (isGlobalEnabled && (!wxs_isArticlePage || normalizedConfig.global_force_article)) {
            await processGlobalSelectors();
        } else if (isDebug) {
            console.log('全局选择器不满足生效条件：', {
                isGlobalEnabled,
                isArticlePage: wxs_isArticlePage,
                globalForceArticle: normalizedConfig.global_force_article
            });
        }
    }
    
    async function getSelectorElements(selectors) {
        if (!selectors?.trim()) return [];
        const selectorList = selectors.split(',').map(s => s.trim()).filter(s => s);
        try {
            return Array.from(document.querySelectorAll(selectorList.join(',')));
        } catch (e) {
            if (isDebug) console.error('选择器解析错误:', e);
            return [];
        }
    }
    
    async function processElements(elements, deep) {
        if (!elements || elements.length === 0) return;
        
        // 将NodeList或单个元素转为数组
        const elementsArray = elements.length ? Array.from(elements) : [elements];
        
        const batchSize = 5;
        for (let i = 0; i < elementsArray.length; i += batchSize) {
            const batch = elementsArray.slice(i, Math.min(i + batchSize, elementsArray.length));
            await Promise.all(batch.map(el => {
                if (el.nodeType === 1) { // 只处理元素节点
                    return processTag(el, deep);
                }
                return Promise.resolve();
            }));
            await new Promise(resolve => setTimeout(resolve, 50));
        }
    }

    // 动态监听
    function watchDynamic() {
        // 混合模式判断
        if (normalizedConfig.run_mode === 'hybrid' && wxs_isUserLoggedIn) {
            return;
        }
        
        const observer = new MutationObserver(mutations => {
            setTimeout(() => {
                mutations.forEach(mutation => {
                    mutation.addedNodes.forEach(node => {
                        if (node.nodeType === 1) {
                            // 处理文章内容动态变化
                            if (wxs_isArticlePage) {
                                if (htmlTags.includes(node.tagName.toLowerCase())) {
                                    ipReady ? processTag(node) : pendingTasks.push(() => processTag(node));
                                } else {
                                    node.querySelectorAll(htmlTags.join(',')).forEach(tag => {
                                        ipReady ? processTag(tag) : pendingTasks.push(() => processTag(tag));
                                    });
                                }
                            }
                            
                            // 处理全局选择器动态变化
                            if (isGlobalEnabled) {
                                // 与initProcessing一致的条件：非文章页 或 强制启用
                                const shouldProcessGlobal = !wxs_isArticlePage || normalizedConfig.global_force_article;
                                if (!shouldProcessGlobal) {
                                    if (isDebug) {
                                        console.log('动态元素不满足全局选择器条件，跳过处理:', node);
                                    }
                                    return; // 不满足条件，跳过
                                }

                                const selectors = [];
                                if (normalizedConfig.jsClassSelectors) { // 规范化配置
                                    selectors.push(...normalizedConfig.jsClassSelectors.split(',').map(s => s.trim()).filter(s => s));
                                }
                                if (normalizedConfig.jsIdSelectors) { // 规范化配置
                                    selectors.push(...normalizedConfig.jsIdSelectors.split(',').map(s => s.trim()).filter(s => s));
                                }
                                
                                selectors.forEach(selector => {
                                    const { selector: parsedSelector, deep } = parseSelector(selector);
                                    try {
                                        if (node.matches(parsedSelector)) {
                                            ipReady ? processTag(node, deep) : pendingTasks.push(() => processTag(node, deep));
                                        }
                                        node.querySelectorAll(parsedSelector).forEach(el => {
                                            ipReady ? processTag(el, deep) : pendingTasks.push(() => processTag(el, deep));
                                        });
                                    } catch (e) {
                                        if (isDebug) {
                                            console.error(`动态监听中无效的选择器: ${selector}`, e);
                                        }
                                    }
                                });
                            }
                        }
                    });
                });
            }, 100);
        });
        
        observer.observe(document.body, { childList: true, subtree: true });
        if (isDebug) {
            console.log('动态内容监听已启动');
        }
    }

    // 启动处理流程
    initProcessing().then(watchDynamic).catch(err => {
        if (isDebug) {
            console.error('水印处理失败:', err);
        }
    });
});

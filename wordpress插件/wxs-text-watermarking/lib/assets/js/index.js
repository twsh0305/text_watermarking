document.addEventListener('DOMContentLoaded', function() {
    // 页面全局变量
    const isUserLoggedIn = window.isUserLoggedIn || false;
    const current_user_id = window.current_user_id || false;
    const isArticlePage = window.isArticlePage || false;

    // 加载PHP配置
    const config = window.wxsWatermarkConfig || {};
    if (config.enable !== 1 || !isArticlePage) return;

    // 爬虫过滤
    const userAgent = navigator.userAgent.toLowerCase();
    const botUAs = config.bot_ua || [];
    if (botUAs.some(bot => userAgent.includes(bot.toLowerCase()))) return;


    // 目标容器
    const articleContainer = document.querySelector('.article-content');
    if (!articleContainer) return;

    // IP获取状态管理
    let pageIP = 'unknown';
    let ipReady = false;
    let pendingWatermarkTasks = [];

    // 获取IP并设置状态
    async function fetchPageIP() {
        try {
            const res = await fetch('/wp-content/plugins/wxs-text-watermarking/fuckip.php');
            const data = await res.json();
            if (data.success) {
                pageIP = data.ip;
            }
        } catch (e) {
            console.error('获取IP失败:', e);
        } finally {
            ipReady = true;
            // 执行所有等待的水印任务
            pendingWatermarkTasks.forEach(task => task());
            pendingWatermarkTasks = [];
        }
    }

    // 立即开始获取IP
    fetchPageIP();

    // 1. 水印信息生成器
    class WatermarkInfo {
        constructor(watermarkContent) {
            this.content = watermarkContent;
        }

        getIP() {
            return pageIP;
        }

        getUser() {
            return isUserLoggedIn 
                ? (current_user_id !== false ? String(current_user_id) : 'user') 
                : 'guest';
        }

        formatTime() {
            const d = new Date();
            return `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')} ${String(d.getHours()).padStart(2, '0')}:${String(d.getMinutes()).padStart(2, '0')}:${String(d.getSeconds()).padStart(2, '0')}`;
        }

        async generateRaw() {
            const parts = [];
            if (this.content.include_ip) parts.push(`IP:${this.getIP()}`);
            if (this.content.include_user) parts.push(`USER:${this.getUser()}`);
            if (this.content.include_time) parts.push(`TIME:${this.formatTime()}`);
            if (this.content.include_custom && this.content.custom_text) parts.push(this.content.custom_text);
            return parts.join('|');
        }
    }

    // 2. 水印处理器
    class WatermarkProcessor {
        constructor(config) {
            this.config = config;
            this.info = new WatermarkInfo(config.watermark_content || {});
            this.minLength = config.min_paragraph_length || 20;
            this.insertMethod = config.insert_method || 3;
            this.random = config.random || { count_type: 2, custom_count: 1, word_based_ratio: 400 };
            this.fixed = config.fixed || { interval: 20 };
            this.isDebug = config.debug_mode === 1;
        }

        byteToChar(byte) {
            return byte < 16 
                ? String.fromCodePoint(0xFE00 + byte) 
                : String.fromCodePoint(0xE0100 + (byte - 16));
        }

        async generateWatermark() {
            const raw = await this.info.generateRaw();
            if (this.isDebug) {
                return `[水印：${raw}]`;
            }
            const bytes = new TextEncoder().encode(raw);
            return Array.from(bytes).map(b => this.byteToChar(b)).join('');
        }

        getInsertCount(textLength) {
            return this.random.count_type === 1 
                ? Math.max(1, this.random.custom_count) 
                : Math.max(1, Math.floor(textLength / this.random.word_based_ratio));
        }

        getRandomPositions(textLength) {
            const positions = [];
            const count = this.getInsertCount(textLength);
            const safeRange = [5, textLength - 5];
            for (let i = 0; i < count; i++) {
                let pos;
                do {
                    pos = Math.floor(Math.random() * (safeRange[1] - safeRange[0])) + safeRange[0];
                } while (positions.some(p => Math.abs(p - pos) < 10));
                positions.push(pos);
            }
            return positions.sort((a, b) => a - b);
        }

        async processText(text) {
            if (text.length < this.minLength) return text;
            const watermark = await this.generateWatermark();
            if (!watermark) return text;

            switch (this.insertMethod) {
                case 1: return text + watermark;
                case 2: {
                    const positions = this.getRandomPositions(text.length);
                    let result = '', lastPos = 0;
                    positions.forEach(pos => {
                        result += text.slice(lastPos, pos);
                        result += watermark;
                        lastPos = pos;
                    });
                    return result + text.slice(lastPos);
                }
                case 3: {
                    let fixedResult = '';
                    for (let i = 0; i < text.length; i++) {
                        fixedResult += text[i];
                        if ((i + 1) % this.fixed.interval === 0 && i < text.length - 1) {
                            fixedResult += watermark;
                        }
                    }
                    return fixedResult;
                }
                default: return text;
            }
        }
    }

    // 3. 内容处理逻辑
    const processor = new WatermarkProcessor(config);

    async function processTextNode(node) {
        if (node.nodeType !== 3 || !node.textContent.trim()) return;
        
        // 等待IP准备好
        if (!ipReady) {
            await new Promise(resolve => pendingWatermarkTasks.push(resolve));
        }
        
        const original = node.textContent;
        const processed = await processor.processText(original);
        if (processed !== original) node.textContent = processed;
    }

    async function processPTag(pTag) {
        if (pTag.tagName !== 'P') return;
        
        // 等待IP准备好
        if (!ipReady) {
            await new Promise(resolve => pendingWatermarkTasks.push(resolve));
        }
        
        const promises = [];
        pTag.childNodes.forEach(child => {
            if (child.nodeType === 3) {
                promises.push(processTextNode(child));
            }
        });
        await Promise.all(promises);
    }

    // 4. 启动执行
    async function initStatic() {
        // 等待IP准备好
        if (!ipReady) {
            await new Promise(resolve => pendingWatermarkTasks.push(resolve));
        }
        
        const pTags = articleContainer.querySelectorAll('p');
        const promises = Array.from(pTags).map(p => processPTag(p));
        await Promise.all(promises);
    }

    function watchDynamic() {
        new MutationObserver(mutations => {
            mutations.forEach(mutation => {
                mutation.addedNodes.forEach(node => {
                    if (node.nodeType === 1) {
                        if (node.tagName === 'P') {
                            // 等待IP准备好后处理
                            if (ipReady) {
                                processPTag(node);
                            } else {
                                pendingWatermarkTasks.push(() => processPTag(node));
                            }
                        } else {
                            Array.from(node.querySelectorAll('p')).forEach(p => {
                                if (ipReady) {
                                    processPTag(p);
                                } else {
                                    pendingWatermarkTasks.push(() => processPTag(p));
                                }
                            });
                        }
                    }
                });
            });
        }).observe(articleContainer, { 
            childList: true, 
            subtree: true, 
            characterData: true 
        });
    }

    // 启动处理流程
    initStatic()
        .then(watchDynamic)
        .catch(err => console.error('水印初始化失败:', err));
});
/**
 * 文本盲水印插件前端JS（优化URL排除逻辑）
 * @create 2025-12-23
 * @author 天无神话 https://wxsnote.cn
 * @GitHub开源地址 https://github.com/twsh0305/text_watermarking
 */

(function() {
    // 水印标识
    const WATERMARK_FLAG = '[文本盲水印:';
    // 已处理节点标记
    const PROCESSED_FLAG = 'data-watermark-processed';

    // 等待DOM加载完成
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initWatermarking);
    } else {
        initWatermarking();
    }
    
    function initWatermarking() {
        // 检查配置是否存在
        if (typeof window.wxstbwConfig === 'undefined') {
            console.warn('文本盲水印：配置未找到，插件可能未启用');
            return;
        }
        
        // 获取配置
        const config = window.wxstbwConfig;
        const isDebug = !!config.debug_mode;
        const isEnabled = !!config.enable;
        
        // 前置检查：优先判断爬虫UA
        if (isEnabled && isBotUserAgent(config, isDebug)) {
            isDebug && console.log('文本盲水印：检测到爬虫UA，跳过水印处理');
            return;
        }
        
        // 前置检查
        if (!isEnabled) {
            isDebug && console.log('文本盲水印：插件未启用');
            return;
        }
        
        // 查找所有的.message元素进行处理
        const messageElements = document.querySelectorAll('div.message[isfirst="1"]');
        if (messageElements.length === 0) {
            if (isDebug) console.log('文本盲水印：未找到isfirst="1"的.message元素');
            return;
        }
        
        isDebug && console.log(`文本盲水印：找到 ${messageElements.length} 个.message元素`);
        
        // 处理每个.message元素
        messageElements.forEach((messageElement, index) => {
            processMessageElement(messageElement, config, isDebug, index);
        });
        
        isDebug && console.log('文本盲水印：所有.message元素处理完成');
    }
    
    function processMessageElement(messageElement, config, isDebug, index) {
        try {
            // 复制原始内容
            const originalHTML = messageElement.innerHTML;
            
            // 创建包裹容器
            const wrapper = document.createElement('div');
            wrapper.className = 'wxs-text-watermark-wrap';
            wrapper.setAttribute('data-watermark-target', 'true');
            wrapper.setAttribute('data-index', index);
            
            // 将原始内容放入包裹容器
            wrapper.innerHTML = originalHTML;
            
            // 替换.message元素的内容
            messageElement.innerHTML = '';
            messageElement.appendChild(wrapper);
            
            // 确保包裹区域可见
            wrapper.style.display = 'block';
            wrapper.style.visibility = 'visible';
            wrapper.style.opacity = '1';
            
            // 开始处理水印
            isDebug && console.log(`文本盲水印：开始处理第 ${index + 1} 个.message元素`);
            processWatermarks(config, wrapper, isDebug);
            isDebug && console.log(`文本盲水印：第 ${index + 1} 个.message元素处理完成`);
            
        } catch (error) {
            console.error(`文本盲水印处理第 ${index + 1} 个.message元素失败：`, error);
        }
    }
    
    // 检查是否为爬虫UA
    function isBotUserAgent(config, isDebug) {
        if (!Array.isArray(config.bot_ua) || config.bot_ua.length === 0) {
            isDebug && console.log('文本盲水印：爬虫UA配置为空，跳过检测');
            return false;
        }
        
        const userAgent = (navigator.userAgent || '').toLowerCase().replace(/\s+/g, ' ').trim();
        isDebug && console.log('文本盲水印：当前用户UA（格式化后）：', userAgent);
        
        const botUAs = config.bot_ua
            .map(bot => (bot || '').toLowerCase().trim())
            .filter(bot => bot.length > 0);
        
        const isBot = botUAs.some(bot => {
            const isMatch = userAgent.includes(bot);
            if (isMatch && isDebug) {
                console.log(`文本盲水印：匹配到爬虫UA标识：${bot}`);
            }
            return isMatch;
        });
        
        return isBot;
    }
    
    // 处理水印核心逻辑
    async function processWatermarks(config, targetElement, isDebug) {
        try {
            // 生成水印内容
            const watermark = await generateWatermark(config, isDebug);
            if (!watermark) {
                isDebug && console.log('文本盲水印：水印内容为空');
                return;
            }
            
            // 仅处理配置中的标签内文本
            processTargetTags(config, watermark, targetElement, isDebug);
        } catch (error) {
            console.error('文本盲水印处理失败：', error);
        }
    }
    
    // 处理配置中的标签
    function processTargetTags(config, watermark, targetElement, isDebug) {
        const tags = Array.isArray(config.html_tags) ? config.html_tags : ['p', 'li'];
        
        tags.forEach(tag => {
            if (!tag || typeof tag !== 'string') return;
            
            const elements = targetElement.getElementsByTagName(tag);
            const elementCount = elements.length;
            isDebug && console.log(`文本盲水印：在目标元素中找到 ${elementCount} 个 <${tag}> 标签`);
            
            Array.from(elements).forEach(element => {
                // 排除代码块、A标签父级
                if (element.closest('pre, code, .code-toolbar, a')) return;
                // 处理标签内的文本节点
                processTextNodesInElement(element, config, watermark, isDebug);
            });
        });
    }
    
    // 处理元素内所有符合条件的文本节点
    function processTextNodesInElement(element, config, watermark, isDebug) {
        const walker = document.createTreeWalker(
            element,
            NodeFilter.SHOW_TEXT,
            {
                acceptNode: function(node) {
                    // 跳过已处理的节点
                    if (node[PROCESSED_FLAG]) return NodeFilter.FILTER_REJECT;
                    
                    const text = node.nodeValue?.trim() || '';
                    // 跳过空文本、代码内文本、A标签内文本
                    if (!text || node.parentElement.closest('pre, code, .code-toolbar, a')) {
                        return NodeFilter.FILTER_REJECT;
                    }
                    
                    // 跳过已包含水印标识的文本
                    if (text.includes(WATERMARK_FLAG)) return NodeFilter.FILTER_REJECT;
                    
                    return NodeFilter.FILTER_ACCEPT;
                }
            }
        );
        
        const textNodes = [];
        let node;
        while (node = walker.nextNode()) {
            textNodes.push(node);
        }
        
        // 处理每个文本节点
        textNodes.forEach(textNode => {
            const originalText = textNode.nodeValue || '';
            const textLength = originalText.replace(/\s+/g, '').length;
            
            // 跳过短文本
            if (textLength < config.min_paragraph_length) {
                return;
            }
            
            // 标记节点为已处理
            textNode[PROCESSED_FLAG] = true;
            
            // 插入水印（智能排除URL和其他特殊内容）
            const processedText = processTextWithWatermark(originalText, watermark, config);
            if (processedText !== originalText) {
                textNode.nodeValue = processedText;
                isDebug && console.log('文本盲水印：已处理文本节点', {
                    原长度: originalText.length,
                    新长度: processedText.length,
                    预览: originalText.substring(0, 50) + '...'
                });
            }
        });
    }
    
    // 判断文本是否为纯URL
    function isUrl(text) {
        const urlRegex = /^(https?:\/\/|ftp:\/\/|www\.)[^\s]+$/i;
        return urlRegex.test(text);
    }
    
    // 判断文本中是否包含URL
    function isContainUrl(text) {
        const urlRegex = /(https?:\/\/|ftp:\/\/|www\.)[^\s]+/i;
        return urlRegex.test(text);
    }
    
    // 生成水印
    async function generateWatermark(config, isDebug) {
        const watermarkParts = [];
        
        // IP信息
        if (config.include_ip) {
            let ip = 'unknown';
            try {
                ip = await wxsTextWatermarkingGetIP();
                isDebug && console.log('文本盲水印：获取到IP：', ip);
            } catch (e) {
                isDebug && console.warn('文本盲水印：获取IP失败', e);
            }
            watermarkParts.push(`IP:${ip}`);
        }
        
        // 用户信息
        if (config.include_user) {
            const userId = window.wxstbw_isUserLoggedIn ? window.wxstbw_current_user_id : 'guest';
            watermarkParts.push(`USER:${userId}`);
        }
        
        // 作者ID信息
        if (config.include_author && window.wxstbw_author_uid) {
            watermarkParts.push(`AUTHOR:${window.wxstbw_author_uid}`);
        }
        
        // 帖子ID信息
        if (config.include_thread && window.wxstbw_thread_id) {
            watermarkParts.push(`THREAD:${window.wxstbw_thread_id}`);
        }
        
        // 时间信息
        if (config.include_time) {
            const now = new Date();
            const timeStr = `${now.getFullYear()}-${String(now.getMonth()+1).padStart(2, '0')}-${String(now.getDate()).padStart(2, '0')} ${String(now.getHours()).padStart(2, '0')}:${String(now.getMinutes()).padStart(2, '0')}:${String(now.getSeconds()).padStart(2, '0')}`;
            watermarkParts.push(`TIME:${timeStr}`);
        }
        
        // 自定义文本
        if (config.include_custom && config.custom_text) {
            watermarkParts.push(config.custom_text);
        }
        
        if (watermarkParts.length === 0) return '';
        
        const watermarkContent = watermarkParts.join('|');
        // 调试模式返回可见水印，生产模式返回不可见变体选择器
        return config.debug_mode ? `[文本盲水印:${watermarkContent}]` : encodeToVariationSelectors(watermarkContent);
    }
    
    // 编码为变体选择器
    function encodeToVariationSelectors(text) {
        let result = '';
        for (let i = 0; i < text.length; i++) {
            const charCode = text.charCodeAt(i);
            if (charCode < 16) {
                result += String.fromCodePoint(0xFE00 + charCode);
            } else if (charCode < 256) {
                result += String.fromCodePoint(0xE0100 + (charCode - 16));
            } else {
                encodeUTF8(text[i]).forEach(byte => {
                    result += byte < 16 ? String.fromCodePoint(0xFE00 + byte) : String.fromCodePoint(0xE0100 + (byte - 16));
                });
            }
        }
        return result;
    }
    
    // UTF-8编码
    function encodeUTF8(char) {
        const codePoint = char.codePointAt(0);
        const bytes = [];
        if (codePoint <= 0x7F) {
            bytes.push(codePoint);
        } else if (codePoint <= 0x7FF) {
            bytes.push(0xC0 | (codePoint >> 6));
            bytes.push(0x80 | (codePoint & 0x3F));
        } else if (codePoint <= 0xFFFF) {
            bytes.push(0xE0 | (codePoint >> 12));
            bytes.push(0x80 | ((codePoint >> 6) & 0x3F));
            bytes.push(0x80 | (codePoint & 0x3F));
        } else {
            bytes.push(0xF0 | (codePoint >> 18));
            bytes.push(0x80 | ((codePoint >> 12) & 0x3F));
            bytes.push(0x80 | ((codePoint >> 6) & 0x3F));
            bytes.push(0x80 | (codePoint & 0x3F));
        }
        return bytes;
    }
    
    // 智能处理文本并插入水印
    function processTextWithWatermark(text, watermark, config) {
        // 如果文本是纯URL，直接返回
        if (isUrl(text)) {
            return text;
        }
        
        // 如果文本包含换行符，逐行处理
        if (text.includes('\n')) {
            return text.split('\n').map(line => {
                const lineTrim = line.trim();
                if (!lineTrim || lineTrim.replace(/\s+/g, '').length < config.min_paragraph_length ||
                    lineTrim.includes(WATERMARK_FLAG)) {
                    return line;
                }
                // 检查是否为纯URL
                if (isUrl(lineTrim)) {
                    return line; // 纯URL不加水印
                }
                // 对非纯URL的行进行智能处理
                return insertWatermarkWithExclusions(line, watermark, config);
            }).join('\n');
        }
        
        // 对非换行文本进行智能处理
        return insertWatermarkWithExclusions(text, watermark, config);
    }
    
    // 智能插入水印，排除特殊内容
    function insertWatermarkWithExclusions(text, watermark, config) {
        if (!text || !watermark) return text;
        
        const insertMethod = config.insert_method || 1;
        
        switch (insertMethod) {
            case 1: // 段落末尾插入
                // 在段落末尾插入，但要确保不是URL的末尾
                return text + watermark;
                
            case 2: // 随机位置插入
                return insertRandomWatermarkWithExclusions(text, watermark, config);
                
            case 3: // 固定间隔插入
                return insertFixedIntervalWatermarkWithExclusions(text, watermark, config);
                
            default:
                return text;
        }
    }
    
    // 随机位置插入
    function insertRandomWatermarkWithExclusions(text, watermark, config) {
        let insertCount = Math.max(1, parseInt(config.random_custom_count) || 1);
        const textLength = text.length;
        if (textLength < 10) insertCount = 1;
        
        // 找出所有需要排除的区域（URL、邮箱、文件路径等）
        const excludeRanges = getExcludeRanges(text);
        
        // 计算可插入的区域
        const insertRanges = getInsertRanges(excludeRanges, textLength);
        
        // 如果没有可插入的区域，返回原文本
        if (insertRanges.length === 0) {
            return text;
        }
        
        // 从可插入区域中随机选择位置
        const positions = new Set();
        let attempts = 0;
        const maxAttempts = insertCount * 10; // 防止无限循环
        
        while (positions.size < insertCount && attempts < maxAttempts) {
            attempts++;
            
            // 随机选择一个可插入区域
            const rangeIndex = Math.floor(Math.random() * insertRanges.length);
            const range = insertRanges[rangeIndex];
            
            // 在该区域内随机选择一个位置
            // 避免选择区域边界位置，以免水印太靠近特殊内容
            const minPos = range.start + 1;
            const maxPos = range.end - 1;
            
            if (maxPos > minPos) {
                const pos = Math.floor(Math.random() * (maxPos - minPos)) + minPos;
                positions.add(pos);
            }
        }
        
        // 按位置排序
        const sortedPositions = Array.from(positions).sort((a, b) => a - b);
        
        // 插入水印
        let result = '';
        let lastPos = 0;
        sortedPositions.forEach((pos, index) => {
            // 累加偏移量
            const offset = index * watermark.length;
            const actualPos = pos + offset;
            result += text.substring(lastPos, pos);
            result += watermark;
            lastPos = pos;
        });
        
        // 拼接剩余文本
        result += text.substring(lastPos);
        return result;
    }
    
    // 固定间隔插入（排除URL等特殊内容）
    function insertFixedIntervalWatermarkWithExclusions(text, watermark, config) {
        const interval = Math.max(5, parseInt(config.fixed_interval) || 20);
        const textLength = text.length;
        
        // 找出所有需要排除的区域
        const excludeRanges = getExcludeRanges(text);
        
        let result = '';
        let currentPos = 0;
        let insertIndex = 0;
        
        while (currentPos < textLength) {
            // 检查当前位置是否在排除区域内
            const isInExcludedRange = excludeRanges.some(range => 
                currentPos >= range.start && currentPos < range.end
            );
            
            if (isInExcludedRange) {
                // 如果在排除区域内，跳过整个区域
                const containingRange = excludeRanges.find(range => 
                    currentPos >= range.start && currentPos < range.end
                );
                if (containingRange) {
                    // 将整个排除区域添加到结果中
                    result += text.substring(currentPos, containingRange.end);
                    currentPos = containingRange.end;
                    continue;
                }
            }
            
            // 添加当前字符
            result += text[currentPos];
            currentPos++;
            
            // 检查是否需要插入水印
            // 避免在文本末尾或排除区域前插入
            if ((currentPos % interval === 0) && 
                currentPos < textLength && 
                !isAtExcludedBoundary(currentPos, excludeRanges)) {
                result += watermark;
                insertIndex++;
            }
        }
        
        return result;
    }
    
    // 获取需要排除的区域（URL、邮箱、文件路径等）
    function getExcludeRanges(text) {
        const excludeRanges = [];
        
        // 1. URL模式
        const urlRegex = /(https?:\/\/[^\s]+|www\.[^\s]+|ftp:\/\/[^\s]+)/gi;
        let urlMatch;
        while ((urlMatch = urlRegex.exec(text)) !== null) {
            excludeRanges.push({
                type: 'url',
                start: urlMatch.index,
                end: urlMatch.index + urlMatch[0].length
            });
        }
        
        // 2. 邮箱模式
        const emailRegex = /[\w.%+-]+@[\w.-]+\.[a-zA-Z]{2,}/gi;
        let emailMatch;
        while ((emailMatch = emailRegex.exec(text)) !== null) {
            excludeRanges.push({
                type: 'email',
                start: emailMatch.index,
                end: emailMatch.index + emailMatch[0].length
            });
        }
        
        // 3. 文件路径模式（Unix和Windows）
        const filePathRegex = /(\/[^/\s]+)+(\/[^/\s]*)?|([a-zA-Z]:\\(?:[^\\/:*?"<>|\r\n]+\\)*[^\\/:*?"<>|\r\n]*)/gi;
        let filePathMatch;
        while ((filePathMatch = filePathRegex.exec(text)) !== null) {
            excludeRanges.push({
                type: 'filepath',
                start: filePathMatch.index,
                end: filePathMatch.index + filePathMatch[0].length
            });
        }
        
        // 4. 数字和特殊代码模式（如版本号、代码片段）
        const codeRegex = /(?:v?\d+\.\d+(?:\.\d+)?|\b[A-Z]{2,10}\d+\b|\b[A-Z]+_\d+\b)/gi;
        let codeMatch;
        while ((codeMatch = codeRegex.exec(text)) !== null) {
            // 只排除较长的代码模式
            if (codeMatch[0].length >= 4) {
                excludeRanges.push({
                    type: 'code',
                    start: codeMatch.index,
                    end: codeMatch.index + codeMatch[0].length
                });
            }
        }
        
        // 按起始位置排序
        excludeRanges.sort((a, b) => a.start - b.start);
        
        // 合并重叠的区域
        const mergedRanges = [];
        for (const range of excludeRanges) {
            if (mergedRanges.length === 0) {
                mergedRanges.push({...range});
            } else {
                const lastRange = mergedRanges[mergedRanges.length - 1];
                if (range.start <= lastRange.end) {
                    // 重叠，合并
                    lastRange.end = Math.max(lastRange.end, range.end);
                } else {
                    mergedRanges.push({...range});
                }
            }
        }
        
        return mergedRanges;
    }
    
    // 获取可插入的区域
    function getInsertRanges(excludeRanges, textLength) {
        const insertRanges = [];
        
        if (excludeRanges.length === 0) {
            // 没有排除区域，整个文本都可以插入
            return [{ start: 0, end: textLength }];
        }
        
        // 第一个区域之前
        if (excludeRanges[0].start > 0) {
            insertRanges.push({
                start: 0,
                end: excludeRanges[0].start
            });
        }
        
        // 排除区域之间的区域
        for (let i = 0; i < excludeRanges.length - 1; i++) {
            const currentEnd = excludeRanges[i].end;
            const nextStart = excludeRanges[i + 1].start;
            
            if (nextStart - currentEnd > 0) {
                insertRanges.push({
                    start: currentEnd,
                    end: nextStart
                });
            }
        }
        
        // 最后一个区域之后
        const lastExclude = excludeRanges[excludeRanges.length - 1];
        if (lastExclude.end < textLength) {
            insertRanges.push({
                start: lastExclude.end,
                end: textLength
            });
        }
        
        return insertRanges;
    }
    
    // 检查是否在排除区域边界
    function isAtExcludedBoundary(position, excludeRanges) {
        return excludeRanges.some(range => 
            position === range.start || position === range.end ||
            position === range.start - 1 || position === range.end + 1
        );
    }
    
    // AJAX获取IP方法
    function wxsTextWatermarkingGetIP() {
        return new Promise(function(resolve, reject) {
            var xhr = new XMLHttpRequest();
            xhr.open('GET', './plugin/wxs_text_watermarking/getip.php', true);
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
                    if (xhr.status === 200) {
                        try {
                            var response = JSON.parse(xhr.responseText);
                            resolve(response.success ? response.ip : 'unknown');
                        } catch (e) {
                            reject(new Error('IP响应解析失败'));
                        }
                    } else {
                        reject(new Error('IP请求网络失败'));
                    }
                }
            };
            xhr.send();
        });
    }
})();
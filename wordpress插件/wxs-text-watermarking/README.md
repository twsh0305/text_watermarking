# 文本盲水印（WordPress插件）

一款专为WordPress博客设计的文本版权保护工具，通过在文章内容中嵌入不可见的盲水印信息，实现原创内容的版权追溯与侵权取证。

[中文文档](https://github.com/twsh0305/text_watermarking/blob/main/README.md) | [English document](https://github.com/twsh0305/text_watermarking/blob/main/README_EN.md)

## 截图
<img width="2539" height="1085" alt="image" src="https://github.com/user-attachments/assets/371b429b-2885-48db-ba43-98280b972307" />
<img width="2538" height="983" alt="image" src="https://github.com/user-attachments/assets/55a8dd33-7ec9-4a59-a189-6092d190b4fb" />
<img width="2536" height="968" alt="image" src="https://github.com/user-attachments/assets/cc79e343-edca-428f-9654-44ed14973540" />
<img width="2312" height="564" alt="image" src="https://github.com/user-attachments/assets/7ab6fa68-6f22-4f0e-b5f2-4f6348cb4015" />


## 核心功能

### 灵活的水印嵌入方式
- **段落末尾插入**：在符合长度要求的段落结尾添加水印，平衡隐蔽性与完整性
- **随机位置插入**：在段落中随机分布水印（支持自定义插入次数或按字数比例自动计算）
- **固定间隔插入**：按设定字数间隔（默认20字）均匀嵌入，适用于长文本场景


### 丰富的水印信息维度
可自定义水印包含的溯源信息（支持组合配置）：
- 访问者IP地址（支持代理场景识别，通过多来源IP检测确保准确性）
- 用户标识（登录用户显示ID，游客标记为"guest"）
- 时间戳（精确到秒的水印生成时间，格式：YYYY-MM-DD HH:MM:SS）
- 自定义文本（支持添加版权声明、网站标识等个性化内容）


### 智能适配机制
- **短段落过滤**：可配置最小段落字数（默认20字），避免在短文本中插入水印导致暴露
- **爬虫识别**：内置搜索引擎爬虫UA过滤规则，不向爬虫插入水印，不影响SEO
- **双端处理模式**：
  - 动态模式（纯PHP）：服务器端处理，适合无缓存场景
  - 静态模式（纯JS）：客户端处理，适合全缓存页面
  - 混合模式：登录用户用PHP处理，游客用JS处理（推荐带缓存的网站）


### 便捷的调试与管理
- **调试模式**：开启后水印以可见文本形式显示（格式：`[水印调试:...]`），便于测试效果
- **直观配置面板**：通过WordPress后台「文本水印」菜单进行全功能配置，支持实时生效
- **数据清理**：卸载插件时自动清除所有配置数据，避免冗余残留


## 工作原理

基于Unicode字符集中的**变体选择器（Variation Selectors）** 实现盲水印：
1. 这些特殊字符（如U+FE00-U+FE0F、U+E0100-U+E01EF）在视觉上不可见，不影响文本阅读体验
2. 水印生成流程：
   - 将原始信息（IP、用户ID等）转换为字节序列
   - 通过映射算法将字节转换为对应的变体选择器字符
3. 嵌入时按配置的插入规则将不可见字符混入文本，提取时通过逆向解析还原原始信息


## 安装要求

- 服务器环境：PHP 7.0+
- WordPress版本：4.7+


## 安装步骤

1. 下载插件源码压缩包
2. 登录WordPress后台，进入「插件」→「安装插件」→「上传插件」
3. 激活插件后，通过左侧菜单「文本水印」进入配置页面
4. 启用插件并根据需求配置水印参数（建议先开启调试模式测试效果）


## 使用指南

### 基础配置
1. 在「基础设置」中启用盲水印，选择运行模式（推荐混合模式）
2. 配置最小段落字数（建议15-30字）和水印插入方式
3. 按需设置随机插入/固定间隔插入的详细参数

### 水印内容配置
在「水印内容设置」中勾选需要包含的信息：
- 访问者IP（默认启用，用于定位传播源头）
- 用户ID（默认启用，区分登录用户与游客）
- 时间戳（默认启用，记录水印生成时间）
- 自定义文本（支持添加网站域名、版权声明等）

### 调试与验证
1. 开启「调试模式」，发布文章后查看内容，可见水印以`[水印调试:...]`形式显示
2. 确认水印插入位置和内容无误后，关闭调试模式即可


## 水印提取

如需检测文本中的水印信息，可通过两种方式：

1. **在线提取工具**：访问[官方水印提取页面](https://wxsnote.cn/wbmsy)，粘贴含水印文本即可解析
2. **代码提取**：使用项目提供的提取函数（示例）：
   ```php
   require 'path/to/extract.php'; // 引入提取工具
   $textWithWatermark = "包含盲水印的文本内容...";
   $extractedInfo = wxs_extractWatermark($textWithWatermark);
   echo "提取的水印信息：" . $extractedInfo;
   ```
## 插件基于以下开源项目
后台框架：[Codestar Framework](https://github.com/Codestar/codestar-framework) 加密方案：[Emoji Encoder](https://github.com/paulgb/emoji-encoder)

## 许可证
本插件基于GPLv3许可证发布（详见LICENSE文件）。  
- 允许自用、修改，但**必须保留原始版权声明**（禁止移除或修改代码中的作者信息）。  
- 若分发修改后的版本，必须以GPLv3或更高版本开源，并提供完整源代码。

## 作者信息

- 作者：天无神话
- 博客：[王先生笔记](https://wxsnote.cn/)
- 原理介绍：[文本盲水印技术实现](https://wxsnote.cn/6395.html)
- QQ群：[399019539](https://jq.qq.com/?_wv=1027&k=eiGEOg3i)
- 开源地址：[GitHub仓库](https://github.com/twsh0305/text_watermarking)

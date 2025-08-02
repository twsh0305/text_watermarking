# 文本盲水印介绍
为文本添加水印，用于将文本版权信息隐藏到文本中，进行版权保护，溯源

开源协议：MIT，这意味着，尽管你可以对软件进行各种操作，但必须保留原作者的版权声明。软件的使用者可以自由地使用、复制、修改、合并、发布、分发、再授权软件及其衍生品

作者：天无神话

作者博客：[王先生笔记](https://wxsnote.cn/)

# 文本盲水印（WordPress插件）

一款为WordPress博客设计的文本盲水印插件，通过在文章内容中嵌入不可见的版权信息（盲水印），实现文本内容的版权保护与溯源追踪。

## 功能特点

- **多种嵌入方式**：支持3种水印插入模式，适配不同场景需求
  - 段落末尾插入：在符合长度的段落末尾添加水印
  - 随机位置插入：在段落文本中随机分布插入点（可配置密度）
  - 固定间隔插入：按设定字数间隔（如每20字）均匀插入
- **丰富水印信息**：可自定义水印包含的内容
  - 访问者IP地址（支持代理场景）
  - 用户标识（登录用户显示ID，游客显示"guest"）
  - 时间戳（水印生成时的精确时间）
  - 自定义文本（如版权声明、网站标识等）
- **智能过滤机制**：
  - 跳过短段落（可配置最小长度），避免水印被轻易发现
  - 自动识别搜索引擎爬虫（内置常见爬虫UA列表），不影响SEO
- **动态内容适配**：通过前端监控机制，对动态加载的文章内容自动补加水印
- **调试模式**：支持开启可见水印模式，方便测试与验证
- **双端处理**：结合PHP后端与JavaScript前端，覆盖登录/未登录全用户场景


## 工作原理

利用Unicode中的**变体选择器字符**（Variation Selectors）实现盲水印：
- 这些字符（如U+FE00-U+FE0F、U+E0100-U+E01EF）在视觉上不可见，不影响文本阅读
- 将水印信息（IP、用户、时间等）转换为字节序列，再映射为对应的变体选择器字符
- 嵌入时将这些不可见字符插入文本中，提取时通过解析这些字符还原原始水印信息


## 安装步骤

1. 下载插件源码，解压得到`text_watermarking`文件夹
2. 将文件夹上传至WordPress的`wp-content/plugins/`目录
3. 登录WordPress后台，进入「插件」页面，激活「文本盲水印」插件


## 配置说明

插件核心配置位于`wordpress插件/text_watermarking.php`中的`$wxs_watermark_config`数组，可根据需求修改：

| 配置项 | 说明 | 可选值 |
|--------|------|--------|
| `enable` | 是否启用水印功能 | 1（启用）、0（禁用） |
| `min_paragraph_length` | 最小段落字数限制（短于该值不插入水印） | 正整数（建议15-30） |
| `insert_method` | 水印插入方式 | 1（末尾）、2（随机）、3（固定间隔） |
| `random.count_type` | 随机插入次数计算模式（仅`insert_method=2`生效） | 1（自定义次数）、2（按字数比例） |
| `random.custom_count` | 每段插入次数（仅`count_type=1`生效） | 正整数 |
| `random.word_based_ratio` | 字数比例（每多少字插入1次，仅`count_type=2`生效） | 正整数（建议200-500） |
| `fixed.interval` | 固定插入间隔（仅`insert_method=3`生效） | 正整数（建议10-30） |
| `watermark_content.include_ip` | 是否包含访问者IP | true/false |
| `watermark_content.include_user` | 是否包含用户标识 | true/false |
| `watermark_content.include_time` | 是否包含时间戳 | true/false |
| `watermark_content.include_custom` | 是否包含自定义文本 | true/false |
| `watermark_content.custom_text` | 自定义文本内容（仅`include_custom=true`生效） | 任意字符串 |
| `bot_ua` | 爬虫UA过滤列表 | 爬虫用户代理字符串数组 |
| `debug_mode` | 是否开启调试模式（水印可见化） | 1（启用）、0（禁用） |


## 使用方法

1. 插件激活后，默认配置下自动生效
2. 登录用户访问时，由PHP后端处理文章内容并插入水印
3. 未登录用户访问时，由前端端JavaScript处理并插入水印
4. 动态加载的内容（如AJAX加载的段落）会被自动监控并补加水印
5. 开启调试模式（`debug_mode=1`）后，水印会以`[水印：内容]`形式显示，方便验证效果


## 水印提取

如需提取文本中的水印信息，可使用项目中的`example.php`提供的提取工具：
```php
// 示例：提取水印
require 'example.php';
$textWithWatermark = "包含盲水印的文本内容...";
$extracted = wxs_extractWatermark($textWithWatermark);
echo "提取的水印信息：" . $extracted;
```

## 作者信息
天无神话

项目地址：[https://github.com/twsh0305/text_watermarking](https://github.com/twsh0305/text_watermarking)

文章说明：[https://wxsnote.cn/暂无](https://wxsnote.cn/暂无)

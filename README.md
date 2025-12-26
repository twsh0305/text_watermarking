# 文本盲水印

文本版权保护工具，通过在文章内容中嵌入不可见的盲水印信息，实现原创内容的版权追溯与侵权取证。

当前已支持的系统：WordPress XiunoBBS

[WordPress中文文档](https://github.com/twsh0305/text_watermarking/tree/main/wordpress%E6%8F%92%E4%BB%B6/wxs-text-watermarking)| [WordPress english document](https://github.com/twsh0305/text_watermarking/blob/main/wordpress%E6%8F%92%E4%BB%B6/wxs-text-watermarking/README_EN.md)

XiunoBBS文档暂时没空写，总之开箱即用，介绍见；[https://wxsnote.cn/7180.html](https://wxsnote.cn/7180.html)

## 截图
wordpress后台
<img width="2537" height="1238" alt="image" src="https://github.com/user-attachments/assets/e865628a-3236-403f-a759-366b80af750e" />
xiunobbs后台
<img width="2537" height="1238" alt="image" src="https://github.com/user-attachments/assets/57a49842-5643-4173-8950-505ff21455cd" />
提取效果展示
<img width="2538" height="1238" alt="image" src="https://github.com/user-attachments/assets/f8c82719-6093-4c4a-b06b-0f273e9114c3" />



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


## 工作原理

基于Unicode字符集中的**变体选择器（Variation Selectors）** 实现盲水印：
1. 这些特殊字符（如U+FE00-U+FE0F、U+E0100-U+E01EF）在视觉上不可见，不影响文本阅读体验
2. 水印生成流程：
   - 将原始信息（IP、用户ID等）转换为字节序列
   - 通过映射算法将字节转换为对应的变体选择器字符
3. 嵌入时按配置的插入规则将不可见字符混入文本，提取时通过逆向解析还原原始信息


## 水印提取

如需检测文本中的水印信息，可通过两种方式：

1. **在线提取工具1**：访问[官方水印提取页面](https://wxsnote.cn/wbmsy)，粘贴含水印文本即可解析
2. **在线提取工具2**：[洪绘文本盲水印](https://textwatermark.zhheo.com/)，粘贴含水印文本即可解析
3. **代码提取**：使用项目提供的提取函数（示例）：
   ```php
   require 'path/to/example.php'; // 引入提取工具
   $textWithWatermark = "包含盲水印的文本内容...";
   $extractedInfo = wxs_extractWatermark($textWithWatermark);
   echo "提取的水印信息：" . $extractedInfo;
   ```


## 插件基于以下开源项目
后台框架：[Codestar Framework](https://github.com/Codestar/codestar-framework) 加密方案：[Emoji Encoder](https://github.com/paulgb/emoji-encoder)

## 许可证
本插件基于GPLv2许可证发布（详见LICENSE文件）。  
- 允许自用、修改，但**必须保留原始版权声明**（禁止移除或修改代码中的作者信息）。  
- 若分发修改后的版本，必须以GPLv2版本开源，并提供完整源代码。

## 作者信息

- 作者：天无神话
- 博客：[王先生笔记](https://wxsnote.cn/)
- 原理介绍：[文本盲水印技术实现](https://wxsnote.cn/6395.html)
- QQ群：[399019539](https://jq.qq.com/?_wv=1027&k=eiGEOg3i)
- 开源地址：[GitHub仓库](https://github.com/twsh0305/text_watermarking)
- wordPress插件地址：[wordpress插件目录](https://cn.wordpress.org/plugins/wxs-text-watermarking/)

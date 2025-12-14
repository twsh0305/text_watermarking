# 文本盲水印

一款专为WordPress博客设计的文本版权保护工具，通过在文章内容中嵌入不可见的盲水印信息，实现原创内容的版权追溯与侵权取证。

[中文文档](https://github.com/twsh0305/text_watermarking/blob/main/README.md) | [English document](https://github.com/twsh0305/text_watermarking/blob/main/README_EN.md)

## 截图
<img width="2539" height="1085" alt="image" src="https://github.com/user-attachments/assets/371b429b-2885-48db-ba43-98280b972307" />
<img width="2538" height="983" alt="image" src="https://github.com/user-attachments/assets/55a8dd33-7ec9-4a59-a189-6092d190b4fb" />
<img width="2536" height="968" alt="image" src="https://github.com/user-attachments/assets/cc79e343-edca-428f-9654-44ed14973540" />
<img width="2312" height="564" alt="image" src="https://github.com/user-attachments/assets/7ab6fa68-6f22-4f0e-b5f2-4f6348cb4015" />

## 更新记录
- 1.0.0 纯function钩子代码
- 1.0.1 创建插件
- 1.0.2 引入CSF框架，创建设置面板
- 1.0.3 js控制
- 1.0.4 部分wordpress设置面板页面空白
- 1.0.5 CSF框架缺失样式的问题
- 1.0.6 引入文件错误
- 1.0.7 标签选择，class元素选择及id容器选择
- 1.0.8 1.使用wp的本地时间，2.如果存在子比主题，则直接调用子比主题的CSF框架，3.修复PHP8.x的报错，4.修复全局js文章不生效的问题
- 1.0.9 1.多语言国际化，2.外部资源本地化，3.遵守WordPress插件开发规范，使用WP函数
- 1.1.0 1.修复js导致的文字错乱，2.增加用户组判断
- 1.1.1 1.增加code和url的排除

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

- 服务器环境：PHP 7.4+
- WordPress版本：6.3+


## 安装步骤

1. 下载插件源码压缩包
2. 登录WordPress后台，进入「插件」→「安装插件」→「上传插件」
3. 激活插件后，通过左侧菜单「文本水印」进入配置页面
4. 启用插件并根据需求配置水印参数（建议先开启调试模式测试效果）

或直接在wordpress插件目录搜索“文本盲水印”

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

1. **在线提取工具1**：访问[官方水印提取页面](https://wxsnote.cn/wbmsy)，粘贴含水印文本即可解析
2. **在线提取工具2**：[洪绘文本盲水印](https://textwatermark.zhheo.com/)，粘贴含水印文本即可解析
3. **代码提取**：使用项目提供的提取函数（示例）：
   ```php
   require 'path/to/example.php'; // 引入提取工具
   $textWithWatermark = "包含盲水印的文本内容...";
   $extractedInfo = wxs_extractWatermark($textWithWatermark);
   echo "提取的水印信息：" . $extractedInfo;
   ```

## 自定义功能

需要您具有基本的开发能力
在插件目录下的lib目录创建func.php，并在插件设置中，点击基础设置->用户组类型选择自定义用户组

func.php文件示例1：
   ```php
<?php
/**
 * Custom 用户组水印控制功能 User Group Watermark Control Function
 * 
 * @param int|null $user_id 当前用户ID，访客为空 Current user ID, visitor is empty
 * @return bool True插入水印，False跳过 True Insert watermark, False Skip
 * 非开发者请勿使用，此处是自定义的演示，请勿直接使用，要配合其它用户组函数使用的，此方案可免更新覆盖
 * Non-developers do not use, here is a custom demo, do not use directly, to cooperate with other user group functions, this scheme can be updated free of coverage
 */
function wxstbw_op_custom($user_id = null) {
    // 示例1：跳过特定用户级别的水印 (主题用户级别1)，Example 1: Skip watermark for a specific user level (subject user level 1)
    if (function_exists('your_theme_get_user_level')) {
        $user_level = your_theme_get_user_level($user_id);
        if ($user_level == 1) {
            return false; // 跳过1级用户的水印，Skip watermark for level 1 users
        }
    }
    
    // 示例2：跳过具有特定Meta值的用户的水印，Example 2: Skipping watermarks for users with specific Meta values
    if ($user_id) {
        $user_meta = get_user_meta($user_id, 'your_custom_field', true);
        if ($user_meta == 'skip_watermark') {
            return false;
        }
    }
    
    // 示例3：跳过VIP用户的水印，Example 3: Skip the watermark for VIP users
    if (function_exists('is_user_vip') && is_user_vip($user_id)) {
        return false;
    }
    
    // 示例4：仅为特定角色插入水印（替代方法），Example 4: Watermarks inserted only for specific characters (alternative)
    if ($user_id) {
        $user = get_user_by('id', $user_id);
        $allowed_roles = ['subscriber', 'customer']; // 为这些角色插入水印，Insert watermarks for these characters
        $disallowed_roles = ['administrator', 'editor']; // 跳过这些角色，Skip these characters
        
        $user_roles = $user->roles;
        foreach ($disallowed_roles as $role) {
            if (in_array($role, $user_roles)) {
                return false;
            }
        }
    }
    
    // 默认值：插入水印，Default: Insert watermark
    return true;
}
   ```

func.php文件示例2(仅子比主题)：
   ```php
<?php
/**
* 根据用户VIP等级控制水印显示
* 
* @param int|null $user_id 当前用户ID，访客为空
* @return bool True插入水印，False跳过
*/
function wxstbw_op_custom($user_id = null) {
 // 仅对有用户ID的情况做VIP等级判断（访客直接返回true，插入水印）
 if ($user_id && function_exists('zib_get_user_vip_level')) {
     // 获取用户VIP等级并转为整数
     $vip_level = (int) zib_get_user_vip_level($user_id);
     // 会员等级大于0时，跳过水印（返回false）
     if ($vip_level > 0) {
         return false;
     }
 }
 
 // 默认返回true（插入水印）：
 // 1. 访客用户
 // 2. 无VIP等级函数时
 // 3. VIP等级≤1的用户
 return true;
}
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
- 插件地址：[wordpress插件目录](https://wordpress.org/plugins/wxs-text-watermarking/)

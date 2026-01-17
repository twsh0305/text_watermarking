# Wxs Text Watermarking

A dedicated text copyright protection tool for WordPress blogs, embedding invisible blind watermarks within article content to enable traceability and infringement evidence collection for original content.

[WordPress中文文档](https://github.com/twsh0305/text_watermarking/tree/main/wordpress%E6%8F%92%E4%BB%B6/wxs-text-watermarking)| [WordPress english document](https://github.com/twsh0305/text_watermarking/blob/main/wordpress%E6%8F%92%E4%BB%B6/wxs-text-watermarking/README_EN.md)

## Screenshots
<img width="2537" height="1238" alt="image" src="https://github.com/user-attachments/assets/49daec6a-fe37-46ee-a14f-d7c8a47694bf" />
<img width="2538" height="1238" alt="image" src="https://github.com/user-attachments/assets/8de403a2-a263-4fea-8148-dad00155dcb4" />
<img width="2540" height="1238" alt="image" src="https://github.com/user-attachments/assets/fab545cf-a2a9-4b9a-8412-847e961b53bd" />
<img width="2538" height="1238" alt="image" src="https://github.com/user-attachments/assets/f87eb960-5a2f-4863-a756-51e9815eb545" />


## Changelog
- 1.0.0 Pure function hook code
- 1.0.1 Plugin creation
- 1.0.2 Introduced CSF framework, created settings panel
- 1.0.3 JS control
- 1.0.4 Fixed blank settings panel issue on some WordPress pages
- 1.0.5 Fixed missing styles issue with CSF framework
- 1.0.6 Fixed incorrect file inclusion
- 1.0.7 Added tag selection, class element selection, and ID container selection
- 1.0.8 1. Use WordPress local time. 2. Directly use the child theme's CSF framework if a child theme exists. 3. Fixed PHP 8.x warnings. 4. Fixed global JS not applying to articles.
- 1.0.9 1. Multi-language internationalization. 2. Localized external resources. 3. Complied with WordPress plugin development standards, using WP functions.
- 1.1.0 1. Fixed text disorder caused by JS. 2. Added user group determination.
- 1.1.1 1. Added exclude code and url
- 1.1.2 Removing cache control

## Core Features

### Flexible Watermark Embedding Methods
- **Insert at Paragraph End**: Adds watermark at the end of qualifying paragraphs, balancing stealth and integrity.
- **Insert at Random Positions**: Distributes watermark randomly within paragraphs (supports custom insertion count or automatic calculation based on word count ratio).
- **Insert at Fixed Intervals**: Embeds watermark evenly at set character intervals (default 20 characters), suitable for long text.

### Rich Watermark Information Dimensions
Customizable watermark content (supports combined configuration):
- Visitor IP Address (Supports proxy detection, ensuring accuracy through multi-source IP checks)
- User Identifier (Shows ID for logged-in users, marks guests as "guest")
- Timestamp (Watermark generation time precise to seconds, format: YYYY-MM-DD HH:MM:SS)
- Custom Text (Supports adding copyright notices, website identifiers, and other personalized content)

### Intelligent Adaptation Mechanisms
- **Short Paragraph Filtering**: Configurable minimum paragraph length (default 20 characters) to avoid watermark exposure in short text.
- **Crawler Recognition**: Built-in search engine crawler UA filtering rules; no watermark inserted for crawlers, ensuring SEO is unaffected.
- **Dual Processing Modes**:
  - Dynamic Mode (Pure PHP): Server-side processing, suitable for non-cached scenarios.
  - Static Mode (Pure JS): Client-side processing, suitable for fully cached pages.
  - Hybrid Mode: PHP processing for logged-in users, JS processing for visitors (recommended for cached websites).

### Convenient Debugging and Management
- **Debug Mode**: When enabled, watermarks are displayed as visible text (format: `[Watermark Debug:...]`) for easy testing.
- **Intuitive Configuration Panel**: Full-featured configuration via the WordPress admin "Text Watermark" menu, supports real-time effect.
- **Data Cleanup**: Automatically removes all configuration data upon plugin uninstallation, avoiding redundant residue.

## How It Works

Based on **Variation Selectors** in the Unicode character set to implement blind watermarking:
1. These special characters (e.g., U+FE00-U+FE0F, U+E0100-U+E01EF) are visually invisible and do not affect the reading experience.
2. Watermark generation process:
   - Converts original information (IP, user ID, etc.) into a byte sequence.
   - Maps bytes to corresponding Variation Selector characters using an algorithm.
3. Embeds invisible characters into the text according to configured insertion rules. Extraction reverses the process to restore the original information.

## Installation Requirements

- Server Environment: PHP 7.4+
- WordPress Version: 6.3+

## Installation Steps

1. Download the plugin source code zip file.
2. Log in to your WordPress admin panel, go to "Plugins" → "Add New" → "Upload Plugin".
3. After activating the plugin, access the configuration page via the left-hand menu "Text Watermark".
4. Enable the plugin and configure watermark parameters as needed (recommended to enable debug mode first for testing).

Or directly search for "Wxs Text Watermarking" in the WordPress plugin directory.

## User Guide

### Basic Configuration
1. Enable blind watermarking in "Basic Settings" and select the operation mode (Hybrid mode recommended).
2. Configure the minimum paragraph length (suggested 15-30 characters) and watermark insertion method.
3. Set detailed parameters for random insertion/fixed interval insertion as needed.

### Watermark Content Configuration
In "Watermark Content Settings", check the information to include:
- Visitor IP (Enabled by default, used to trace the propagation source).
- User ID (Enabled by default, distinguishes logged-in users from guests).
- Timestamp (Enabled by default, records watermark generation time).
- Custom Text (Supports adding website domain, copyright notice, etc.).

### Debugging and Verification
1. Enable "Debug Mode", publish an article, and view the content to see watermarks displayed as `[Watermark Debug:...]`.
2. After confirming the watermark insertion positions and content are correct, disable debug mode.

## Watermark Extraction

To detect watermark information in text, use one of the following methods:

1. **Online Extraction Tool 1**: Visit the [official watermark extraction page](https://wxsnote.cn/wbmsy), paste the watermarked text for parsing.
2. **Online Extraction Tool 2**: [Heo Text Watermark](https://textwatermark.zhheo.com/), paste the watermarked text for parsing.
3. **Code Extraction**: Use the provided extraction function (example):
   ```php
   require 'path/to/example.php'; // Include the extraction tool
   $textWithWatermark = "Watermarked text content...";
   $extractedInfo = wxs_extractWatermark($textWithWatermark);
   echo "Extracted watermark information: " . $extractedInfo;
   ```
## Customization Features

Requires basic development skills.
Create a func.php file in the plugin's lib directory, and in the plugin settings, go to "Basic Settings" → "User Group Type" and select "Custom User Group".

Example for func.php:
   ```php
<?php
/**
* Custom User Group Watermark Control Function
*
* @param int|null $user_id Current user ID, null for visitors
* @return bool True to insert watermark, False to skip
* Non-developers should not use this. This is a custom demo. Do not use directly. It should be used in conjunction with other user group functions. This method avoids overwriting on updates.
*/
function wxstbw_op_custom($user_id = null) {
 // Example 1: Skip watermark for users of a specific level (theme user level 1)
 if (function_exists('your_theme_get_user_level')) {
     $user_level = your_theme_get_user_level($user_id);
     if ($user_level == 1) {
         return false; // Skip watermark for level 1 users
     }
 }

 // Example 2: Skip watermark for users with specific meta values
 if ($user_id) {
     $user_meta = get_user_meta($user_id, 'your_custom_field', true);
     if ($user_meta == 'skip_watermark') {
         return false;
     }
 }

 // Example 3: Skip watermark for VIP users
 if (function_exists('is_user_vip') && is_user_vip($user_id)) {
     return false;
 }

 // Example 4: Insert watermark only for specific roles (alternative method)
 if ($user_id) {
     $user = get_user_by('id', $user_id);
     $allowed_roles = ['subscriber', 'customer']; // Insert watermark for these roles
     $disallowed_roles = ['administrator', 'editor']; // Skip these roles

     $user_roles = $user->roles;
     foreach ($disallowed_roles as $role) {
         if (in_array($role, $user_roles)) {
             return false;
         }
     }
 }

 // Default: Insert watermark
 return true;
}
   ```

## Plugin Based on Open Source Projects
Admin Framework：[Codestar Framework](https://github.com/Codestar/codestar-framework) Encryption Scheme：[Emoji Encoder](https://github.com/paulgb/emoji-encoder)

## License
This plugin is released under the GPLv2 license (see LICENSE file for details).
- Permitted for personal use and modification, but the original copyright notice must be retained (removal or modification of author information in the code is prohibited).
- If distributing a modified version, it must be open-sourced under the GPLv2 license with complete source code provided.

## Author Information

- Author：天无神话
- Blog：[Mr. Wang's Notes](https://wxsnote.cn/)
- Technical Principle Introduction：[Text Blind Watermarking Implementation](https://wxsnote.cn/6395.html)
- QQ Group：[399019539](https://jq.qq.com/?_wv=1027&k=eiGEOg3i)
- Open Source Repository：[GitHub Repository](https://github.com/twsh0305/text_watermarking)
- Plugin Page：[WordPress Plugin Directory](https://wordpress.org/plugins/wxs-text-watermarking/)

## Star History

[![Star History Chart](https://api.star-history.com/svg?repos=twsh0305/text_watermarking&type=date&legend=top-left)](https://www.star-history.com/#twsh0305/text_watermarking&type=date&legend=top-left)

# Text Blind Watermark

A specialized copyright protection tool for WordPress blogs that embeds invisible blind watermark information into article content, enabling copyright traceability and infringement evidence collection for original works.

[中文文档](https://github.com/twsh0305/text_watermarking/blob/main/README.md) | [English document](https://github.com/twsh0305/text_watermarking/blob/main/README_EN.md)

## Screenshots
<img width="2539" height="1085" alt="image" src="https://github.com/user-attachments/assets/371b429b-2885-48db-ba43-98280b972307" />
<img width="2538" height="983" alt="image" src="https://github.com/user-attachments/assets/55a8dd33-7ec9-4a59-a189-6092d190b4fb" />
<img width="2536" height="968" alt="image" src="https://github.com/user-attachments/assets/cc79e343-edca-428f-9654-44ed14973540" />
<img width="2312" height="564" alt="image" src="https://github.com/user-attachments/assets/7ab6fa68-6f22-4f0e-b5f2-4f6348cb4015" />

## Changelog
- 1.0.0 Pure function hook code
- 1.0.1 Plugin creation
- 1.0.2 Introduced CSF framework, created settings panel
- 1.0.3 JS control
- 1.0.4 Blank settings panel on some WordPress pages
- 1.0.5 Missing CSS for CSF framework
- 1.0.6 Incorrect file inclusion
- 1.0.7 Tag selection, class element selection, and ID container selection
- 1.0.8 1. Use WordPress local time, 2. Directly use the CSF framework if the Zibai theme exists, 3. Fixed PHP 8.x errors, 4. Fixed global JS not working on articles
- 1.0.9 1. Multilingual internationalization, 2. Localization of external resources, 3. Complies with WordPress plugin development standards, uses WP functions
- 1.1.0 1. Fix js text confusion, 2. Add user group judgment

## Core Features

### Flexible Watermark Embedding Methods
- **Insert at Paragraph End**: Adds watermark at the end of paragraphs meeting length requirements, balancing concealment and completeness
- **Insert at Random Positions**: Distributes watermarks randomly within paragraphs (supports custom insertion frequency or automatic calculation based on word count ratio)
- **Insert at Fixed Intervals**: Embeds watermarks evenly at set character intervals (default 20 characters), suitable for long text scenarios

### Rich Watermark Information Dimensions
Customizable watermark traceability information (supports combined configuration):
- Visitor IP Address (Supports proxy scenario identification, ensures accuracy through multi-source IP detection)
- User ID (Logged-in users display ID, guests marked as "guest")
- Timestamp (Watermark generation time accurate to the second, format: YYYY-MM-DD HH:MM:SS)
- Custom Text (Supports adding personalized content like copyright notices, website identifiers)

### Intelligent Adaptation Mechanism
- **Short Paragraph Filtering**: Configurable minimum paragraph length (default 20 characters) to avoid exposing watermarks in short text
- **Crawler Identification**: Built-in search engine crawler UA filtering rules; no watermark insertion for crawlers, does not affect SEO
- **Dual-End Processing Modes**:
  - Dynamic Mode (Pure PHP): Server-side processing, suitable for non-cached scenarios
  - Static Mode (Pure JS): Client-side processing, suitable for fully cached pages
  - Mixed Mode: PHP processing for logged-in users, JS processing for guests (recommended for websites with caching)

### Convenient Debugging & Management
- **Debug Mode**: When enabled, watermarks are displayed as visible text (format: `[Watermark Debug:...]`), facilitating effect testing
- **Intuitive Configuration Panel**: Full-feature configuration via WordPress backend "Text Watermark" menu, supports real-time effect
- **Data Cleanup**: Automatically clears all configuration data upon plugin uninstallation, avoiding residual data

## How It Works

Based on **Variation Selectors** in the Unicode character set to implement blind watermarking:
1. These special characters (e.g., U+FE00-U+FE0F, U+E0100-U+E01EF) are visually invisible and do not affect the reading experience
2. Watermark generation process:
   - Converts original information (IP, user ID, etc.) into a byte sequence
   - Maps bytes to corresponding variation selector characters via an algorithm
3. Embeds invisible characters into text according to configured insertion rules; extraction reverses the process to restore original information

## Installation Requirements

- Server Environment: PHP 7.4+
- WordPress Version: 6.3+

## Installation Steps

1. Download the plugin source code zip file
2. Log in to WordPress admin, go to "Plugins" → "Add New" → "Upload Plugin"
3. After activation, access the configuration page via the left menu "Text Watermark"
4. Enable the plugin and configure watermark parameters as needed (recommend enabling debug mode first for testing)

## Usage Guide

### Basic Configuration
1. Enable blind watermark in "Basic Settings", select operation mode (recommended: mixed mode)
2. Configure minimum paragraph length (suggested: 15-30 characters) and watermark insertion method
3. Set detailed parameters for random/fixed interval insertion as needed

### Watermark Content Configuration
Select information to include in "Watermark Content Settings":
- Visitor IP (enabled by default, used to locate propagation source)
- User ID (enabled by default, distinguishes logged-in users from guests)
- Timestamp (enabled by default, records watermark generation time)
- Custom Text (supports adding website domain, copyright notice, etc.)

### Debugging & Verification
1. Enable "Debug Mode", publish an article, and view the content; watermarks will be visible as `[Watermark Debug:...]`
2. After confirming correct watermark insertion position and content, disable debug mode

## Watermark Extraction

To detect watermark information in text, use either method:

1. **Online Extraction Tool**: Visit the [official watermark extraction page](https://wxsnote.cn/wbmsy), paste watermarked text for parsing
2. **Code Extraction**: Use the provided extraction function (example):
   ```php
   require 'path/to/extract.php'; // Import extraction tool
   $textWithWatermark = "Text content containing blind watermark...";
   $extractedInfo = wxs_extractWatermark($textWithWatermark);
   echo "Extracted watermark information: " . $extractedInfo;
   ```

## Plugin based on the following open-source projects
background frame：[Codestar Framework](https://github.com/Codestar/codestar-framework) encryption scheme：[Emoji Encoder](https://github.com/paulgb/emoji-encoder)

## License
This plug-in is released under the GPLv2 license (see LICENSE file for details).  
- Use and modification are permitted, but **the original copyright notice** must be retained (removal or modification of author information in the code is prohibited).  
- If a modified version is distributed, it must be open-sourced as GPLv3 or later, and the full source code must be available.

## information of the author

- author：天无神话
- blog：[Mr. Wang's Notes](https://wxsnote.cn/)
- introduction of the principle：[implementation of text blind watermark technology](https://wxsnote.cn/6395.html)
- QQ groups：[399019539](https://jq.qq.com/?_wv=1027&k=eiGEOg3i)
- Open Source Address：[GitHub](https://github.com/twsh0305/text_watermarking)
- Telegram groups：[https://t.me/+mRFcDJGSk_A4NjNl](https://t.me/+mRFcDJGSk_A4NjNl)
- Plugin address：[WordPress Plugin](https://wordpress.org/plugins/wxs-text-watermarking/)

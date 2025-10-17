# Text Blind Watermark

A specialized text copyright protection tool designed for WordPress blogs. It enables copyright tracing and infringement evidence collection of original content by embedding invisible blind watermark information in article content.

[中文文档](https://github.com/twsh0305/text_watermarking/blob/main/README.md) | [English document](https://github.com/twsh0305/text_watermarking/blob/main/README_EN.md)

## Screenshots
<img width="2539" height="1085" alt="image" src="https://github.com/user-attachments/assets/371b429b-2885-48db-ba43-98280b972307" />
<img width="2538" height="983" alt="image" src="https://github.com/user-attachments/assets/55a8dd33-7ec9-4a59-a189-6092d190b4fb" />
<img width="2536" height="968" alt="image" src="https://github.com/user-attachments/assets/cc79e343-edca-428f-9654-44ed14973540" />
<img width="2312" height="564" alt="image" src="https://github.com/user-attachments/assets/4516b880-1ded-434d-a560-f31e1756a2ea" />

## update log
- 1.0.0 Pure function hook code
- 1.0.1 Added: Plugin creation
- 1.0.2 Added: Introduced CSF framework and created settings panel
- 1.0.3 New: JS control
- 1.0.4 Fixed: Blank pages on some WordPress settings panel pages
- 1.0.5 Fixed: Missing styles issue with CSF framework
- 1.0.6 Fixed: File import errors
- 1.0.7 Added: Tag selection, class element selection and ID container selection
- 1.0.8 Fixed: 1. Use WP local time; 2. Directly call the CSF framework of Zibi Theme if Zibi Theme exists; 3. Fixed PHP 8.x errors; 4. Fixed the issue where global JS doesn’t work on articles
- 1.0.9 Fixed: Internationalization for multilingual translation

## Core Features

### Flexible Watermark Embedding Methods
- **Insert at paragraph end**: Add watermarks at the end of paragraphs that meet length requirements, balancing invisibility and integrity
- **Random position insertion**: Distribute watermarks randomly in paragraphs (supports custom insertion count or automatic calculation by word ratio)
- **Fixed interval insertion**: Embed uniformly at set word intervals (default 20 words), suitable for long text scenarios


### Rich Watermark Information Dimensions
Customizable traceability information in watermarks (supports combined configuration):
- Visitor IP address (supports proxy scenario recognition, ensuring accuracy through multi-source IP detection)
- User identification (displays ID for logged-in users, marks "guest" for visitors)
- Timestamp (watermark generation time accurate to seconds, format: YYYY-MM-DD HH:MM:SS)
- Custom text (supports adding copyright statements, website identifiers, and other personalized content)


### Intelligent Adaptation Mechanism
- **Short paragraph filtering**: Configurable minimum paragraph word count (default 20 words) to avoid watermark exposure in short texts
- **Crawler recognition**: Built-in search engine crawler UA filtering rules, no watermarks inserted for crawlers to avoid affecting SEO
- **Dual-end processing modes**:
  - Dynamic mode (PHP-only): Server-side processing, suitable for non-cached scenarios
  - Static mode (JS-only): Client-side processing, suitable for fully cached pages
  - Hybrid mode: PHP processing for logged-in users, JS processing for visitors (recommended for websites with caching)


### Convenient Debugging and Management
- **Debug mode**: When enabled, watermarks are displayed as visible text (format: `[Watermark Debug:...]`) for easy effect testing
- **Intuitive configuration panel**: Full-function configuration through the WordPress backend "Text Watermark" menu, supporting real-time activation
- **Data cleaning**: Automatically clears all configuration data when uninstalling the plugin to avoid redundant residues


## Working Principle

Based on **Variation Selectors** in the Unicode character set to implement blind watermarks:
1. These special characters (such as U+FE00-U+FE0F, U+E0100-U+E01EF) are visually invisible and do not affect text reading experience
2. Watermark generation process:
   - Convert original information (IP, user ID, etc.) into byte sequences
   - Convert bytes into corresponding variation selector characters through a mapping algorithm
3. During embedding, invisible characters are mixed into the text according to configured insertion rules; original information is restored through reverse parsing during extraction


## Installation Requirements

- Server environment: PHP 7.0+
- WordPress version: 4.7+


## Installation Steps

1. Download the plugin source code zip package
2. Log in to the WordPress backend, go to "Plugins" → "Add New" → "Upload Plugin"
3. After activating the plugin, access the configuration page through the left menu "Text Watermark"
4. Enable the plugin and configure watermark parameters as needed (it is recommended to enable debug mode first for testing)


## User Guide

### Basic Configuration
1. Enable blind watermark in "Basic Settings", select operation mode (hybrid mode recommended)
2. Configure minimum paragraph word count (15-30 words recommended) and watermark insertion method
3. Set detailed parameters for random insertion/fixed interval insertion as needed

### Watermark Content Configuration
Check the information to be included in "Watermark Content Settings":
- Visitor IP (enabled by default, used to locate propagation sources)
- User ID (enabled by default, distinguishes between logged-in users and visitors)
- Timestamp (enabled by default, records watermark generation time)
- Custom text (supports adding website domain, copyright statement, etc.)

### Debugging and Verification
1. Enable "Debug Mode", view content after publishing articles; visible watermarks will display in the form of `[Watermark Debug:...]`
2. After confirming the watermark insertion positions and content are correct, turn off debug mode


## Watermark Extraction

To detect watermark information in text, you can use two methods:

1. **Online extraction tool**: Visit the [official watermark extraction page](https://wxsnote.cn/wbmsy), paste the watermarked text for parsing
2. **Code extraction**: Use the extraction function provided by the project (example):
   ```php
   require 'path/to/extract.php'; // Import extraction tool
   $textWithWatermark = "Text content containing blind watermark...";
   $extractedInfo = wxs_extractWatermark($textWithWatermark);
   echo "Extracted watermark information: " . $extractedInfo;
   ```

## Plugin based on the following open-source projects
background frame：[Codestar Framework](https://github.com/Codestar/codestar-framework) encryption scheme：[Emoji Encoder](https://github.com/paulgb/emoji-encoder)

## License
This plug-in is released under the GPLv3 license (see LICENSE file for details).  
- Use and modification are permitted, but **the original copyright notice** must be retained (removal or modification of author information in the code is prohibited).  
- If a modified version is distributed, it must be open-sourced as GPLv3 or later, and the full source code must be available.

## information of the author

- author：There is no myth
- blog：[Mr. Wang's Notes](https://wxsnote.cn/)
- introduction of the principle：[implementation of text blind watermark technology](https://wxsnote.cn/6395.html)
- QQ groups：[399019539](https://jq.qq.com/?_wv=1027&k=eiGEOg3i)
- Open Source Address：[GitHub](https://github.com/twsh0305/text_watermarking)


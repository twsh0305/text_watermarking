=== Wxs Text Watermarking ===
Contributors: twsh0305
Donate link: https://wxsnote.cn/zanzhu
Tags: blind, watermark, copyright, protection, text
Requires at least: 6.3
Tested up to: 6.9
Stable tag: 1.1.1
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Embeds invisible blind watermarks to enable copyright tracing and infringement evidence collection for original content.

== Description ==
This is a specialized text copyright protection tool designed for WordPress blogs. It enables copyright tracing and infringement evidence collection of original content by embedding invisible blind watermark information in article content.

Core Features:
-- Flexible Watermark Embedding Methods: Insert at paragraph end (balances invisibility and integrity), random position insertion (supports custom count or auto-calculation by word ratio), fixed interval insertion (default 20 words, suitable for long texts).
-- Rich Watermark Information Dimensions: Customizable traceability info including visitor IP (supports proxy recognition), user identification (logged-in ID/guest mark), timestamp (accurate to seconds, YYYY-MM-DD HH:MM:SS), and custom text (copyright statements, website identifiers).
-- Intelligent Adaptation Mechanism: Short paragraph filtering (configurable min word count, default 20), crawler recognition (no watermarks for search engine crawlers to avoid SEO impact), dual-end processing modes (dynamic PHP-only for non-cached scenarios, static JS-only for cached pages, hybrid mode recommended for cached sites).
-- Convenient Debugging and Management: Debug mode (displays visible watermarks as [Watermark Debug:...]), intuitive configuration panel (via WordPress backend "Text Watermark" menu), data cleaning (auto-clears config on uninstallation).

Working Principle:
Based on Variation Selectors in the Unicode character set (`U+FE00-U+FE0F`, `U+E0100-U+E01EF`) which are visually invisible. Watermark generation converts info (IP, user ID, etc.) to byte sequences, then to variation selector characters via mapping algorithm. Invisible characters are embedded per rules and restored via reverse parsing during extraction.

Installation Requirements:
- Server environment: PHP 7.4
- WordPress version: 6.3

User Guide:
Basic Configuration:
1. Enable blind watermark in "Basic Settings", select operation mode (hybrid mode recommended).
2. Configure minimum paragraph word count (15-30 recommended) and insertion method.
3. Set parameters for random/fixed interval insertion as needed.

Watermark Content Configuration:
Check desired info in "Watermark Content Settings": Visitor IP (default enabled), User ID (default enabled), Timestamp (default enabled), Custom text (supports domain/copyright statements).

Debugging and Verification:
1. Enable "Debug Mode", publish article to view visible watermarks (`[Watermark Debug:...]`).
2. Confirm positions and content are correct, then turn off debug mode.

Watermark Extraction:
1. Online tool: Visit [Official text blind watermark](https://wxsnote.cn/wbmsy), paste text for parsing.
2. Online tool: Visit [Heo Text Watermark](https://textwatermark.zhheo.com/), paste text for parsing.
3. Use the extraction function provided in the [GitHub](https://github.com/twsh0305/text_watermarking/) source code (file path: `example.php`).

Plugin based on open-source projects:
- Background framework: [Codestar Framework](https://github.com/Codestar/codestar-framework)
- Encryption scheme: [Emoji Encoder](https://github.com/paulgb/emoji-encoder)

License Details:
Use and modification permitted, but original copyright notice must be retained (prohibits removing/modifying author info in code). Modified versions must be open-sourced as GPLv2 or later with full source code available.

Author Information:
- Author: twsh0305
- Blog: [Mr. Wang's Notes](https://wxsnote.cn/)
- Principle Introduction: [Implementation of text blind watermark technology](https://wxsnote.cn/6395.html)
- QQ Group: [399019539](https://jq.qq.com/?_wv=1027&k=eiGEOg3i)
- Open Source Address: [GitHub](https://github.com/twsh0305/text_watermarking)

== Frequently Asked Questions ==
= Are the blind watermarks visible to readers? =
No. The watermarks use Unicode Variation Selectors that are visually invisible and do not affect reading experience or text layout.

= Will the plugin affect my website's SEO? =
No. It has built-in search engine crawler UA filtering rules, so no watermarks are inserted for crawlers, avoiding any impact on SEO.

= How to extract watermark information from suspected infringing text? =
Two methods: 1. Use the online tool at [Official text blind watermark](https://wxsnote.cn/wbmsy) or [Heo Text Watermark](https://textwatermark.zhheo.com/) by pasting the text. 2.Use the extraction function provided in the [GitHub](https://github.com/twsh0305/text_watermarking/) source code (file path: `example.php`).

= Does the plugin support cached WordPress sites? =
Yes. It offers three processing modes: hybrid mode (recommended) uses PHP for logged-in users and JS for visitors, ensuring watermarks work normally on cached sites.

= What happens to the plugin's configuration when I uninstall it? =
The plugin automatically clears all configuration data during uninstallation, leaving no redundant residues in the database.

== Screenshots ==
1. Plugin basic settings panel interface
2. Watermark content configuration page
3. Debug mode effect display page
4. Online watermark extraction tool interface

== Installation ==

1. Download the plugin source code zip package.
2. Log in to WordPress backend, go to "Plugins" → "Add New" → "Upload Plugin".
3. After activation, access the configuration page via left menu "Text Watermark".
4. Enable the plugin and configure parameters (enable debug mode first for testing).

== Changelog ==

= 1.1.1 =
* Added exclude code and url

= 1.1.0 =
* Added: User Rights Control
* Fixed: js code causes content location disorder

= 1.0.9 =
* Added: Multilingual internationalization, 
* Fixed: External resource localization
* Fixed: Compliance with WordPress plugin development specifications, using WP functions

To read the changelog for Wxs Text Watermarking, please navigate to the <a href="https://github.com/twsh0305/text_watermarking/">Github page</a>.

== Upgrade Notice ==

= 1.1.1 =
Added exclude code and url

= 1.1.0 =
Fixed js code to avoid displaying text confusion, added user group judgment to decide whether to insert text blind watermarking for specified users according to user permissions, and loaded custom function judgment to judge whether to insert text blind watermarking according to your theme or plug-in user group

= 1.0.9 =
Fixed multilingual translation internationalization issues, ensuring proper display in different language environments.

= 1.0.8 =
Resolved 4 key issues: WP local time usage, Zibi Theme CSF Framework compatibility, PHP 8.x errors, and global JS article ineffectiveness. Critical for PHP 8.x users.

= 1.0.7 =
Added element selection functions (tag, class, ID), enabling more precise watermark insertion positions. Upgrade for flexible configuration.

= 1.0.6 =
Fixed file import errors that caused plugin loading failures. Must upgrade if experiencing activation issues.

= 1.0.5 =
Fixed missing styles in the CSF Framework settings panel. Upgrade to restore normal interface display.

= 1.0.4 =
Resolved blank page issue on some WordPress settings panels. Upgrade if encountering configuration page access failures.

= 1.0.3 =
Added JS control functionality, laying the foundation for static mode. Recommended for users needing cached page support.

= 1.0.2 =
Introduced CSF Framework and created settings panel for visual configuration. Upgrade from 1.0.1 for easier management.

= 1.0.1 =
Completed basic plugin creation. Required upgrade from 1.0.0 for formal use.

= 1.0.0 =
Initial version with pure function hook code. Upgrade to later versions for complete functionality.

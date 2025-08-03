<?php
/**
 * @author: https://github.com/twsh0305
 * @description: 文本水印功能
 * @version: 1.0.5
 * 文章地址：https://wxsnote.cn/
 * 开源地址：https://github.com/twsh0305/text_watermarking
 * 说明：该功能在文本内容中插入盲水印
 */
// 变体选择器块定义
define('VARIATION_SELECTOR_START', 0xfe00);
define('VARIATION_SELECTOR_END', 0xfe0f);
define('VARIATION_SELECTOR_SUPPLEMENT_START', 0xe0100);
define('VARIATION_SELECTOR_SUPPLEMENT_END', 0xe01ef);

/**
 * 将字节转换为变体选择器字符
 * @param int $byte 字节值(0-255)
 * @return string|null 变体选择器字符
 */
function wxs_toVariationSelector($byte) {
    if ($byte >= 0 && $byte < 16) {
        return mb_chr(VARIATION_SELECTOR_START + $byte, 'UTF-8');
    } elseif ($byte >= 16 && $byte < 256) {
        return mb_chr(VARIATION_SELECTOR_SUPPLEMENT_START + ($byte - 16), 'UTF-8');
    }
    return null;
}

/**
 * 从变体选择器字符提取字节
 * @param int $codePoint 字符码点
 * @return int|null 对应的字节值
 */
function wxs_fromVariationSelector($codePoint) {
    if ($codePoint >= VARIATION_SELECTOR_START && $codePoint <= VARIATION_SELECTOR_END) {
        return $codePoint - VARIATION_SELECTOR_START;
    } elseif ($codePoint >= VARIATION_SELECTOR_SUPPLEMENT_START && $codePoint <= VARIATION_SELECTOR_SUPPLEMENT_END) {
        return ($codePoint - VARIATION_SELECTOR_SUPPLEMENT_START) + 16;
    }
    return null;
}

/**
 * 嵌入盲水印
 * @param string $text 原始文本
 * @param string $watermark 水印内容
 * @return string 带水印的文本
 */
function wxs_embedWatermark($text, $watermark) {
    if (empty($watermark)) {
        return $text;
    }

    // 获取水印的UTF-8原始字节
    $watermarkBytes = [];
    $watermarkBinary = $watermark;
    $length = strlen($watermarkBinary);
    for ($i = 0; $i < $length; $i++) {
        $watermarkBytes[] = ord($watermarkBinary[$i]);
    }

    $chars = preg_split('//u', $text, -1, PREG_SPLIT_NO_EMPTY);
    $result = '';
    $watermarkIndex = 0;
    $watermarkTotal = count($watermarkBytes);

    // 修复：确保嵌入所有水印字节，而不仅限于原始文本长度
    foreach ($chars as $char) {
        $result .= $char;
        if ($watermarkTotal > 0) {
            $byte = $watermarkBytes[$watermarkIndex];
            $selector = wxs_toVariationSelector($byte);
            if ($selector !== null) {
                $result .= $selector;
            }
            $watermarkIndex = ($watermarkIndex + 1) % $watermarkTotal;
        }
    }

    // 添加剩余未嵌入的水印字节
    while ($watermarkIndex < $watermarkTotal) {
        $byte = $watermarkBytes[$watermarkIndex];
        $selector = wxs_toVariationSelector($byte);
        if ($selector !== null) {
            $result .= $selector;
        }
        $watermarkIndex++;
    }

    return $result;
}

/**
 * 提取盲水印
 * @param string $text 带水印的文本
 * @return string 提取的水印
 */
function wxs_extractWatermark($text) {
    $watermarkBytes = [];

    // 提取所有变体选择器对应的字节
    $chars = preg_split('//u', $text, -1, PREG_SPLIT_NO_EMPTY);
    foreach ($chars as $char) {
        $codePoint = mb_ord($char, 'UTF-8');
        $byte = wxs_fromVariationSelector($codePoint);
        if ($byte !== null) {
            $watermarkBytes[] = $byte;
        }
    }

    if (empty($watermarkBytes)) {
        return '';
    }

    // 转换字节数组为UTF-8字符串
    $watermarkBinary = '';
    foreach ($watermarkBytes as $byte) {
        $watermarkBinary .= chr($byte);
    }

    return mb_check_encoding($watermarkBinary, 'UTF-8') ? $watermarkBinary : '';
}
// 测试用例（原始文本较短，水印较长）
$original = "短文本，我说了，这不行";
$watermark = "版权所有©2025 完整水印内容";

// 嵌入水印
$withWatermark = wxs_embedWatermark($original, $watermark);

// 提取水印
$extracted = wxs_extractWatermark($withWatermark);

echo "原始水印: " . $watermark . "\n";
echo "提取水印: " . $extracted . "\n";

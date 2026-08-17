<?php

namespace WDL;

class Markdown {
    public static function escapeHtml($str) {
        // Match JS escapeHtml logic exactly
        return str_replace(
            ['&', '<', '>', '"'],
            ['&amp;', '&lt;', '&gt;', '&quot;'],
            (string)$str
        );
    }

    public static function sanitizeHtml($html) {
        $badScheme = '/^\s*(?:javascript|data|vbscript):/i';
        
        // Neutralize dangerous href/src URL schemes
        $html = preg_replace_callback(
            '/\b(href|src)\s*=\s*(?:"([^"]*)"|\'([^\']*)\')/i',
            function($m) use ($badScheme) {
                $attr = $m[1];
                $url = isset($m[2]) && $m[2] !== '' ? $m[2] : ($m[3] ?? '');
                if (preg_match($badScheme, $url)) {
                    return "{$attr}=\"#\"";
                }
                return $m[0];
            },
            $html
        );
        
        // Strip inline event handlers (e.g. onclick)
        $html = preg_replace('/\son\w+\s*=\s*(?:"[^"]*"|\'[^\']*\')/i', '', $html);
        return $html;
    }

    public static function renderInlineMarkdown($text) {
        if ($text === null || $text === '') {
            return '';
        }
        
        $html = self::escapeHtml($text);
        
        // 1. Code blocks: `code`
        $html = preg_replace('/`([^`]+)`/', '<code>$1</code>', $html);
        
        // 2. Bold: **text** or __text__
        $html = preg_replace('/\\*\\*(.*?)\\*\\*/s', '<strong>$1</strong>', $html);
        $html = preg_replace('/__(.*?)__/s', '<strong>$1</strong>', $html);
        
        // 3. Italic: *text* (avoiding spaces and double asterisks) or _text_ (avoiding word borders)
        $html = preg_replace('/(?<!\*)\*(?!\s)([^*]+?)(?<!\s)\*(?!\*)/s', '<em>$1</em>', $html);
        $html = preg_replace('/(?<!\w)_(?!\s)([^_]+?)(?<!\s)_(?!\w)/s', '<em>$1</em>', $html);
        
        // 4. Links: [text](url)
        $html = preg_replace('/\[([^\]]+)\]\(([^)]+)\)/s', '<a href="$2">$1</a>', $html);
        
        return self::sanitizeHtml($html);
    }
}

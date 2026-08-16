<?php
declare(strict_types=1);

namespace App\Lib;

final class Minify
{
    /**
     * Strips the whitespace/newlines our templates use for readability. Safe by construction:
     * <textarea> content is pulled out and restored byte-for-byte untouched (it's the one place
     * we render literal, whitespace-sensitive text — a post body a guest is re-editing after a
     * validation error), and only whitespace that sits *between* tags is removed, never
     * whitespace inside a text node (so multi-word sentences keep their spaces).
     */
    public static function html(string $html): string
    {
        $blocks = [];
        $protected = preg_replace_callback(
            '/<textarea\b[^>]*>.*?<\/textarea>/is',
            static function (array $m) use (&$blocks): string {
                $key = "\x00" . count($blocks) . "\x00";
                $blocks[$key] = $m[0];
                return $key;
            },
            $html
        );

        $collapsed = preg_replace('/>\s+</', '><', $protected);
        // Whatever whitespace is left is template indentation/line breaks around text content
        // (e.g. a line that starts with text right after a tag, like "&nbsp;|&nbsp;<a...>") —
        // collapse each such run to a single space so text nodes don't lose their separation.
        $collapsed = preg_replace('/[ \t]*\n[ \t]*/', ' ', $collapsed);
        $collapsed = trim($collapsed);

        return strtr($collapsed, $blocks);
    }

    /** No comments or string values with meaningful whitespace exist in our stylesheet, so a plain collapse is safe. */
    public static function css(string $css): string
    {
        $css = preg_replace('/\s+/', ' ', $css);
        $css = preg_replace('/\s*([{}:;,])\s*/', '$1', $css);
        return trim($css, " ;");
    }
}

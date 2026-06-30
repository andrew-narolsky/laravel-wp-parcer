<?php

namespace App\Values;

readonly class LinkedContent
{
    public static function build(string $content, string $anchor, string $targetUrl): string
    {
        $link = '<a href="' . $targetUrl . '">' . $anchor . '</a>';

        $pos = strpos($content, $anchor);
        $linked = $pos !== false
            ? substr_replace($content, $link, $pos, strlen($anchor))
            : $content . ' ' . $link;

        return '<p>' . $linked . '</p>';
    }
}

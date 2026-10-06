<?php

namespace App\Services;

class ItemNormalizer
{
    public static function normalize(?string $name): string
    {
        $cfg = config('purchase');
        $raw = trim((string) $name);
        $s = mb_strtolower($raw);

        // remove serial numbers: (S/N: ...)
        $s = preg_replace('/\([^)]*s\/n:[^)]*\)/u', ' ', $s);
        // strip leading qty words: "12 Pair Hand Gloves" -> "Hand Gloves"
        $s = preg_replace('/^\s*\d+\s*(pairs?|pcs|pc|psc|paket|packet|doz|dozen|box|set|yeard|yard)\b\.?\s+/u', '', $s);

        $s = str_replace(array_keys($cfg['phrases']), array_values($cfg['phrases']), $s);
        $s = str_replace(['″', '”', '“', '"'], ' inch ', $s);
        $s = preg_replace('/(\d)([a-z])/u', '$1 $2', $s);               // 1mm -> 1 mm

        $s = preg_replace('/[^\p{L}\p{M}\p{N}\.\/]+/u', ' ', $s);       // keep 2.5 and 1/2
        $s = preg_replace('/(?<!\d)\.|\.(?!\d)/u', ' ', $s);
        $s = preg_replace('/(?<!\d)\/|\/(?!\d)/u', ' ', $s);

        $tokens = preg_split('/\s+/u', trim($s), -1, PREG_SPLIT_NO_EMPTY);
        $tokens = array_map(fn ($t) => $cfg['words'][$t] ?? $t, $tokens);
        $tokens = array_values(array_diff($tokens, $cfg['stopwords']));
        $tokens = array_values(array_unique(preg_split('/\s+/', implode(' ', $tokens), -1, PREG_SPLIT_NO_EMPTY)));
        sort($tokens);   // word order no longer matters: "3" SS Clamp" = "SS Clamp 3""

        return $tokens ? implode(' ', $tokens) : mb_strtolower($raw);
    }

    public static function similarity(string $a, string $b): float
    {
        $ta = explode(' ', $a);
        $tb = explode(' ', $b);
        $inter = count(array_intersect($ta, $tb));
        $union = count(array_unique(array_merge($ta, $tb)));
        $min = min(count($ta), count($tb));
        if ($union === 0 || $min === 0) {
            return 0.0;
        }
        return (($inter / $union) + ($inter / $min)) / 2;
    }
}
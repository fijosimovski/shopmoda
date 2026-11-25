<?php

namespace App\Support;

// Small helpers for Serbian (Latin) texts.
class Sr
{
    /**
     * Serbian has three plural forms: 1 par, 2 para, 5 pari (21 par, 22 para, 11-14 pari).
     *   Sr::plural(3, 'model', 'modela', 'modela')  => "3 modela"
     *   Sr::plural(1, 'patika', 'patike', 'patika') => "1 patika"
     */
    public static function plural(int $count, string $one, string $few, string $many, bool $withNumber = true): string
    {
        $mod10 = $count % 10;
        $mod100 = $count % 100;

        $word = match (true) {
            $mod10 === 1 && $mod100 !== 11 => $one,
            $mod10 >= 2 && $mod10 <= 4 && ($mod100 < 12 || $mod100 > 14) => $few,
            default => $many,
        };

        return $withNumber ? $count . ' ' . $word : $word;
    }
}

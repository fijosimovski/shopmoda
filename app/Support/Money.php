<?php

namespace App\Support;

// Prices as text, e.g. 12999 => "12.999 RSD". The @money Blade directive uses this too.
class Money
{
    public static function format(float $amount): string
    {
        return number_format($amount, 0, ',', '.') . ' RSD';
    }
}

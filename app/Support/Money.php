<?php

namespace App\Support;

class Money
{
    public static function format($amount, string $symbol = 'Rs '): string
    {
        return $symbol . number_format((float) $amount, 0);
    }

    public static function raw($amount): float
    {
        return (float) $amount;
    }
}
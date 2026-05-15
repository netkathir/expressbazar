<?php

namespace App\Support;

use Carbon\CarbonInterface;

class StoreDate
{
    public static function date(?CarbonInterface $date): string
    {
        return $date ? $date->format('d M Y') : '-';
    }

    public static function dateTime(?CarbonInterface $date): string
    {
        return $date ? $date->format('d M Y, h:i A') : '-';
    }
}

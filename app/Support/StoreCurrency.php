<?php

namespace App\Support;

use App\Models\SystemConfig;
use Illuminate\Support\Facades\Schema;

class StoreCurrency
{
    private static ?string $code = null;

    public static function code(): string
    {
        if (self::$code !== null) {
            return self::$code;
        }

        if (! Schema::hasTable('system_config')) {
            return self::$code = 'INR';
        }

        $configured = SystemConfig::query()
            ->where('config_key', 'store_currency')
            ->value('config_value');

        $code = strtoupper(trim((string) $configured));

        return self::$code = preg_match('/^[A-Z]{3}$/', $code) ? $code : 'INR';
    }

    public static function format(float|int|string|null $amount, int $decimals = 2): string
    {
        $value = is_numeric($amount) ? (float) $amount : 0.0;

        return self::code() . ' ' . number_format($value, $decimals);
    }

    public static function jsConfig(): array
    {
        return [
            'code' => self::code(),
            'decimals' => 0,
            'locale' => self::code() === 'INR' ? 'en-IN' : 'en-US',
        ];
    }
}

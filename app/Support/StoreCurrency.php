<?php

namespace App\Support;

use App\Models\SystemConfig;
use Illuminate\Support\Facades\Schema;

class StoreCurrency
{
    private static ?string $code = null;

    public static function currencies(): array
    {
        return [
            'AED' => ['symbol' => 'د.إ', 'name' => 'United Arab Emirates Dirham'],
            'AUD' => ['symbol' => '$', 'name' => 'Australian Dollar'],
            'BDT' => ['symbol' => '৳', 'name' => 'Bangladeshi Taka'],
            'CAD' => ['symbol' => '$', 'name' => 'Canadian Dollar'],
            'CNY' => ['symbol' => '¥', 'name' => 'Chinese Yuan'],
            'EUR' => ['symbol' => '€', 'name' => 'Euro'],
            'GBP' => ['symbol' => '£', 'name' => 'British Pound'],
            'INR' => ['symbol' => '₹', 'name' => 'Indian Rupee'],
            'JPY' => ['symbol' => '¥', 'name' => 'Japanese Yen'],
            'LKR' => ['symbol' => 'Rs', 'name' => 'Sri Lankan Rupee'],
            'MYR' => ['symbol' => 'RM', 'name' => 'Malaysian Ringgit'],
            'NPR' => ['symbol' => 'Rs', 'name' => 'Nepalese Rupee'],
            'PKR' => ['symbol' => 'Rs', 'name' => 'Pakistani Rupee'],
            'SAR' => ['symbol' => '﷼', 'name' => 'Saudi Riyal'],
            'SGD' => ['symbol' => '$', 'name' => 'Singapore Dollar'],
            'USD' => ['symbol' => '$', 'name' => 'US Dollar'],
            'ZAR' => ['symbol' => 'R', 'name' => 'South African Rand'],
        ];
    }

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

        return self::symbol() . number_format($value, $decimals);
    }

    public static function symbol(?string $code = null): string
    {
        $currencyCode = strtoupper(trim((string) ($code ?: self::code())));

        return self::currencies()[$currencyCode]['symbol'] ?? $currencyCode.' ';
    }

    public static function jsConfig(): array
    {
        return [
            'code' => self::code(),
            'symbol' => self::symbol(),
            'decimals' => 0,
            'locale' => self::code() === 'INR' ? 'en-IN' : 'en-US',
        ];
    }
}

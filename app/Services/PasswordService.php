<?php

namespace App\Services;

class PasswordService
{
    public const LENGTH = 8;

    public const REGEX = '/^(?=.*[A-Z])(?=.*[^A-Za-z0-9]).{8}$/';

    public const MESSAGE = 'Password must be exactly 8 characters and include at least 1 uppercase letter and 1 special character.';

    public static function generate(int $length = self::LENGTH): string
    {
        $length = min(max(2, $length), self::LENGTH);
        $uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $lowercase = 'abcdefghijklmnopqrstuvwxyz';
        $numbers = '0123456789';
        $special = '@#$%&*!';
        $all = $uppercase.$lowercase.$numbers;

        $characters = [
            $uppercase[random_int(0, strlen($uppercase) - 1)],
            $special[random_int(0, strlen($special) - 1)],
        ];

        for ($i = count($characters); $i < $length; $i++) {
            $characters[] = $all[random_int(0, strlen($all) - 1)];
        }

        return self::shuffle($characters);
    }

    public static function rule(bool $required = true): array
    {
        return [
            $required ? 'required' : 'nullable',
            'string',
            'regex:'.self::REGEX,
        ];
    }

    public static function validationMessages(string $field = 'password'): array
    {
        return [
            $field.'.regex' => self::MESSAGE,
        ];
    }

    private static function shuffle(array $characters): string
    {
        for ($i = count($characters) - 1; $i > 0; $i--) {
            $j = random_int(0, $i);
            [$characters[$i], $characters[$j]] = [$characters[$j], $characters[$i]];
        }

        return implode('', $characters);
    }
}

<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UploadedImage
{
    private const ROOT = 'uploads';
    private const PUBLIC_ROOT = 'storage/uploads';

    public static function store(UploadedFile $file, string $folder, string $prefix): string
    {
        $folder = trim($folder, '/\\');
        $directory = self::ROOT.'/'.$folder;

        $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'jpg');
        $filename = $prefix.'_'.Str::uuid()->toString().'.'.$extension;
        Storage::disk('public')->putFileAs($directory, $file, $filename);

        return self::normalize(self::PUBLIC_ROOT.'/'.$folder.'/'.$filename);
    }

    public static function delete(?string $path): void
    {
        $fullPath = self::publicPath($path);

        if ($fullPath && File::exists($fullPath)) {
            File::delete($fullPath);
        }

        $legacyPath = self::legacyPublicPath($path);

        if ($legacyPath && $legacyPath !== $fullPath && File::exists($legacyPath)) {
            File::delete($legacyPath);
        }
    }

    public static function normalize(?string $path): ?string
    {
        if ($path === null) {
            return null;
        }

        $path = trim(str_replace('\\', '/', $path));

        if ($path === '') {
            return null;
        }

        if (filter_var($path, FILTER_VALIDATE_URL)) {
            return $path;
        }

        $path = ltrim($path, '/');

        if (str_starts_with($path, 'public/')) {
            $path = substr($path, 7);
        }

        if (str_starts_with($path, 'app/public/')) {
            $path = 'storage/'.substr($path, 11);
        }

        return $path;
    }

    public static function publicPath(?string $path): ?string
    {
        $path = self::normalize($path);

        if ($path && filter_var($path, FILTER_VALIDATE_URL)) {
            return null;
        }

        if (str_starts_with((string) $path, self::PUBLIC_ROOT.'/')) {
            return storage_path('app/public/'.substr($path, 8));
        }

        if (str_starts_with((string) $path, self::ROOT.'/')) {
            $storedPath = storage_path('app/public/'.$path);

            if (File::exists($storedPath)) {
                return $storedPath;
            }
        }

        return $path ? public_path($path) : null;
    }

    private static function legacyPublicPath(?string $path): ?string
    {
        $path = self::normalize($path);

        if (! $path || filter_var($path, FILTER_VALIDATE_URL)) {
            return null;
        }

        return public_path($path);
    }
}

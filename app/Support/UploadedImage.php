<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class UploadedImage
{
    private const ROOT = 'uploads';

    public static function store(UploadedFile $file, string $folder, string $prefix): string
    {
        $folder = trim($folder, '/\\');
        $directory = public_path(self::ROOT.'/'.$folder);

        if (! File::exists($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'jpg');
        $filename = $prefix.'_'.Str::uuid()->toString().'.'.$extension;
        $file->move($directory, $filename);

        return self::normalize(self::ROOT.'/'.$folder.'/'.$filename);
    }

    public static function delete(?string $path): void
    {
        $fullPath = self::publicPath($path);

        if ($fullPath && File::exists($fullPath)) {
            File::delete($fullPath);
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

        return $path;
    }

    public static function publicPath(?string $path): ?string
    {
        $path = self::normalize($path);

        if ($path && filter_var($path, FILTER_VALIDATE_URL)) {
            return null;
        }

        return $path ? public_path($path) : null;
    }
}

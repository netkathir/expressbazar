<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Storage;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('orders:auto-cancel-pending {minutes=30}', function ($minutes) {
    $minutes = max(1, (int) $minutes);
    $count = app(\App\Services\VendorOrderWorkflowService::class)->autoCancelPending($minutes);

    $this->info("Auto cancelled {$count} pending orders.");
})->purpose('Auto cancel pending vendor orders after the configured minutes');

Artisan::command('uploads:migrate-public {--force : Overwrite files that already exist in storage}', function () {
    $sourceRoot = public_path('uploads');

    if (! File::isDirectory($sourceRoot)) {
        $this->info('No public/uploads folder found. Nothing to migrate.');

        return 0;
    }

    $copied = 0;
    $skipped = 0;
    $disk = Storage::disk('public');
    $force = (bool) $this->option('force');

    foreach (File::allFiles($sourceRoot) as $file) {
        if ($file->getFilename() === '.gitkeep') {
            continue;
        }

        $relativePath = str_replace('\\', '/', $file->getRelativePathname());
        $targetPath = 'uploads/'.$relativePath;

        if (! $force && $disk->exists($targetPath)) {
            $skipped++;

            continue;
        }

        $stream = fopen($file->getRealPath(), 'rb');
        $disk->put($targetPath, $stream);

        if (is_resource($stream)) {
            fclose($stream);
        }

        $copied++;
    }

    $this->info("Migrated {$copied} upload file(s) to storage/app/public/uploads.");

    if ($skipped > 0) {
        $this->info("Skipped {$skipped} existing file(s). Use --force to overwrite them.");
    }

    return 0;
})->purpose('Copy existing public/uploads files into stable storage/app/public/uploads');

Schedule::command('orders:auto-cancel-pending 30')->everyFiveMinutes();

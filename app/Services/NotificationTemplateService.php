<?php

namespace App\Services;

use App\Models\NotificationTemplate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class NotificationTemplateService
{
    public function findActive(string $trigger, ?string $channel = 'email'): ?NotificationTemplate
    {
        if (! Schema::hasTable('notification_templates')) {
            return null;
        }

        $normalizedTrigger = $this->normalizeKey($trigger);
        $hasTriggerKey = Schema::hasColumn('notification_templates', 'trigger_key');
        $hasStatus = Schema::hasColumn('notification_templates', 'status');
        $hasIsActive = Schema::hasColumn('notification_templates', 'is_active');

        return NotificationTemplate::query()
            ->when($channel, fn ($query) => $query->where('channel', Str::lower($channel)))
            ->when($hasStatus, fn ($query) => $query->where('status', 'active'))
            ->when($hasIsActive, fn ($query) => $query->where('is_active', 1))
            ->latest()
            ->get()
            ->first(function (NotificationTemplate $template) use ($normalizedTrigger, $hasTriggerKey) {
                return ($hasTriggerKey && $this->normalizeKey((string) $template->trigger_key) === $normalizedTrigger)
                    || $this->normalizeKey($template->notification_type) === $normalizedTrigger
                    || $this->normalizeKey($template->template_name) === $normalizedTrigger;
            });
    }

    public function render(string $body, array $data): string
    {
        $normalizedData = [];

        foreach ($data as $key => $value) {
            $normalizedData[Str::lower((string) $key)] = $value;
        }

        return preg_replace_callback('/{{\s*([A-Za-z0-9_.-]+)\s*}}/', function (array $matches) use ($data, $normalizedData) {
            $key = $matches[1];
            $normalizedKey = Str::lower($key);

            if (! array_key_exists($key, $data) && ! array_key_exists($normalizedKey, $normalizedData)) {
                return $matches[0];
            }

            $value = $data[$key] ?? $normalizedData[$normalizedKey];

            if (is_array($value) || is_object($value)) {
                return $matches[0];
            }

            return (string) $value;
        }, $body);
    }

    private function normalizeKey(string $value): string
    {
        return Str::of($value)
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', ' ')
            ->squish()
            ->toString();
    }
}

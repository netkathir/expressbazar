<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class NotificationLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'template_id',
        'recipient_type',
        'recipient_id',
        'channel',
        'message',
        'status',
        'error_message',
    ];

    public function template()
    {
        return $this->belongsTo(NotificationTemplate::class, 'template_id');
    }

    public function displayTemplateName(): string
    {
        if ($this->template?->template_name) {
            return $this->template->template_name;
        }

        if ($this->channel === 'in_app' && $this->decodedMessageValue('order_id')) {
            return 'Vendor Order Alert';
        }

        if ($this->channel === 'email') {
            return 'Fallback Email Template';
        }

        return 'Template not mapped';
    }

    public function displayChannel(): string
    {
        return match (Str::lower((string) $this->channel)) {
            'sms' => 'SMS',
            'in_app' => 'In App',
            default => Str::headline((string) $this->channel),
        };
    }

    public function displayRecipient(): string
    {
        $type = Str::lower((string) $this->recipient_type);
        $label = match ($type) {
            'vendor' => 'Vendor',
            'customer' => 'Customer',
            'admin' => 'Admin',
            default => Str::headline((string) $this->recipient_type ?: 'User'),
        };

        $name = $this->recipientName($type);

        if ($name) {
            return "{$label}: {$name}";
        }

        return $this->recipient_id ? "{$label} #{$this->recipient_id}" : $label;
    }

    public function displayErrorMessage(): string
    {
        if ($this->error_message) {
            return $this->error_message;
        }

        return $this->status === 'failed' ? 'Failed without detailed error.' : 'No error';
    }

    private function recipientName(string $type): ?string
    {
        if (! $this->recipient_id) {
            return null;
        }

        if ($type === 'vendor') {
            return Vendor::query()->whereKey($this->recipient_id)->value('vendor_name');
        }

        if (in_array($type, ['customer', 'admin', 'user'], true)) {
            return User::query()->whereKey($this->recipient_id)->value('name');
        }

        return null;
    }

    private function decodedMessageValue(string $key): mixed
    {
        $message = json_decode((string) $this->message, true);

        return is_array($message) ? ($message[$key] ?? null) : null;
    }
}

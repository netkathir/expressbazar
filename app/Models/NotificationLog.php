<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
}

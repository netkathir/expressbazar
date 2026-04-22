<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotificationTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'template_name',
        'notification_type',
        'channel',
        'subject',
        'message_body',
        'status',
    ];

    public function logs()
    {
        return $this->hasMany(NotificationLog::class, 'template_id');
    }
}

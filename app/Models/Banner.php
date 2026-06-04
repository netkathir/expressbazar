<?php

namespace App\Models;

use App\Support\UploadedImage;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Banner extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'subtitle',
        'image_path',
        'link_url',
        'status',
        'sort_order',
    ];

    public function getImagePathAttribute($value): ?string
    {
        return UploadedImage::normalize($value);
    }
}

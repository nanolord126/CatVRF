<?php

declare(strict_types=1);

namespace App\Domains\Social\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * Модель Поста / Shorts (Социальная сеть)
 */
final class SocialPost extends Model
{
    use HasFactory, BelongsToTenant, SoftDeletes;

    protected $table = 'social_posts';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'user_id',
        'content',
        'type',             // text, image, video, shorts
        'media_url',        // S3 path
        'thumbnail_url',
        'transcoding_status', // pending, processing, completed, failed
        'view_count',
        'like_count',
        'correlation_id',
        'tags',
    ];

    protected $casts = [
        'view_count' => 'integer',
        'like_count' => 'integer',
        'tags' => 'json',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            $model->uuid = $model->uuid ?? (string) Str::uuid();
            $model->correlation_id = $model->correlation_id ?? request()->header('X-Correlation-ID', (string) Str::uuid());
            $model->transcoding_status = $model->transcoding_status ?? 'pending';
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

    public function comments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(SocialComment::class, 'post_id');
    }
}

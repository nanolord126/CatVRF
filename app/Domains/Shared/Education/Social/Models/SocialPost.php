<?php

declare(strict_types=1);

namespace App\Domains\Education\Social\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class SocialPost extends Model
{
    use HasFactory;
    use SoftDeletes;
    use TenantScoped;

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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(SocialComment::class, 'post_id');
    }
}

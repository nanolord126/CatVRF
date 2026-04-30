<?php

declare(strict_types=1);

namespace App\Domains\Staff\Domain\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * StaffPost — пост во внутренней социальной сети.
 * CatVRF 2026 — PRODUCTION MANDATORY.
 */
final class StaffPost extends Model
{
    use SoftDeletes;

    protected $table = 'staff_posts';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'author_id',
        'content',
        'type',
        'attachments',
        'likes_count',
        'comments_count',
        'shares_count',
        'is_pinned',
        'allow_comments',
    ];

    protected $casts = [
        'attachments' => 'json',
        'likes_count' => 'integer',
        'comments_count' => 'integer',
        'shares_count' => 'integer',
        'is_pinned' => 'boolean',
        'allow_comments' => 'boolean',
    ];

    protected $hidden = [
        'deleted_at',
    ];

    public function author(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'author_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Tenant::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(StaffPostComment::class);
    }

    public function likes(): HasMany
    {
        return $this->hasMany(StaffLike::class, 'likeable_id')
                    ->where('likeable_type', StaffPost::class);
    }

    // Scopes
    public function scopePinned($query)
    {
        return $query->where('is_pinned', true);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeRecent($query)
    {
        return $query->orderByDesc('created_at');
    }

    public function scopePopular($query)
    {
        return $query->orderByDesc('likes_count')
                     ->orderByDesc('comments_count');
    }

    // Accessors
    public function getIsAchievementAttribute(): bool
    {
        return $this->type === 'achievement';
    }

    public function getIsAnnouncementAttribute(): bool
    {
        return $this->type === 'announcement';
    }

    public function getHasAttachmentsAttribute(): bool
    {
        return !empty($this->attachments);
    }
}

<?php

declare(strict_types=1);

namespace App\Domains\Staff\Domain\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * StaffPostComment — комментарий к посту.
 * CatVRF 2026 — PRODUCTION MANDATORY.
 */
final class StaffPostComment extends Model
{
    protected $table = 'staff_post_comments';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'post_id',
        'author_id',
        'parent_id',
        'content',
        'likes_count',
    ];

    protected $casts = [
        'likes_count' => 'integer',
    ];

    public function post(): BelongsTo
    {
        return $this->belongsTo(StaffPost::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'author_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(StaffPostComment::class);
    }

    public function replies(): HasMany
    {
        return $this->hasMany(StaffPostComment::class, 'parent_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Tenant::class);
    }

    // Scopes
    public function scopeTopLevel($query)
    {
        return $query->whereNull('parent_id');
    }

    public function scopeReplies($query)
    {
        return $query->whereNotNull('parent_id');
    }

    // Accessors
    public function getIsReplyAttribute(): bool
    {
        return $this->parent_id !== null;
    }

    public function getHasRepliesAttribute(): bool
    {
        return $this->replies()->count() > 0;
    }
}

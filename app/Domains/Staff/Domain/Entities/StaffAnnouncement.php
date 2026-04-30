<?php

declare(strict_types=1);

namespace App\Domains\Staff\Domain\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

/**
 * StaffAnnouncement — объявление для сотрудников.
 * CatVRF 2026 — PRODUCTION MANDATORY.
 */
final class StaffAnnouncement extends Model
{
    use SoftDeletes;

    protected $table = 'staff_announcements';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'author_id',
        'title',
        'content',
        'type',
        'priority',
        'target_audience',
        'attachments',
        'published_at',
        'expires_at',
    ];

    protected $casts = [
        'attachments' => 'json',
        'published_at' => 'datetime',
        'expires_at' => 'datetime',
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

    public function reads(): HasMany
    {
        return $this->hasMany(StaffAnnouncementRead::class);
    }

    // Scopes
    public function scopePublished($query)
    {
        return $query->where('published_at', '<=', now());
    }

    public function scopeUnpublished($query)
    {
        return $query->where('published_at', '>', now());
    }

    public function scopeActive($query)
    {
        return $query->where('published_at', '<=', now())
                     ->where(function ($q) {
                         $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
                     });
    }

    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<', now());
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeHighPriority($query)
    {
        return $query->whereIn('priority', ['high', 'urgent']);
    }

    // Accessors
    public function getIsPublishedAttribute(): bool
    {
        return $this->published_at && $this->published_at->lte(now());
    }

    public function getIsExpiredAttribute(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function getIsActiveAttribute(): bool
    {
        return $this->is_published && !$this->is_expired;
    }

    public function getReadCountAttribute(): int
    {
        return $this->reads()->count();
    }

    public function getHasAttachmentsAttribute(): bool
    {
        return !empty($this->attachments);
    }
}

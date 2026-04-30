<?php

declare(strict_types=1);

namespace App\Domains\Staff\Domain\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * StaffMessageGroup — группа сообщений (канал, чат проекта).
 * CatVRF 2026 — PRODUCTION MANDATORY.
 */
final class StaffMessageGroup extends Model
{
    protected $table = 'staff_message_groups';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'owner_id',
        'name',
        'description',
        'type',
        'is_private',
        'member_count',
    ];

    protected $casts = [
        'is_private' => 'boolean',
        'member_count' => 'integer',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'owner_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Tenant::class);
    }

    public function members(): HasMany
    {
        return $this->hasMany(StaffMessageGroupMember::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(StaffMessage::class, 'group_id');
    }

    // Scopes
    public function scopePublic($query)
    {
        return $query->where('is_private', false);
    }

    public function scopePrivate($query)
    {
        return $query->where('is_private', true);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    // Accessors
    public function getIsPublicAttribute(): bool
    {
        return !$this->is_private;
    }
}

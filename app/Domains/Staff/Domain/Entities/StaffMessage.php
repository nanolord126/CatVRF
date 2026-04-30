<?php

declare(strict_types=1);

namespace App\Domains\Staff\Domain\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * StaffMessage — сообщение сотрудника (личное или в группе).
 * CatVRF 2026 — PRODUCTION MANDATORY.
 */
final class StaffMessage extends Model
{
    protected $table = 'staff_messages';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'sender_id',
        'recipient_id',
        'group_id',
        'content',
        'attachments',
        'read_at',
        'is_deleted_for_sender',
        'is_deleted_for_recipient',
    ];

    protected $casts = [
        'attachments' => 'json',
        'read_at' => 'datetime',
        'is_deleted_for_sender' => 'boolean',
        'is_deleted_for_recipient' => 'boolean',
    ];

    public function sender(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'sender_id');
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'recipient_id');
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(StaffMessageGroup::class, 'group_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Tenant::class);
    }

    public function reactions(): MorphMany
    {
        return $this->morphMany(StaffMessageReaction::class, 'reactionable');
    }

    // Scopes
    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }

    public function scopeRead($query)
    {
        return $query->whereNotNull('read_at');
    }

    public function scopeDirect($query)
    {
        return $query->whereNull('group_id');
    }

    public function scopeGroup($query)
    {
        return $query->whereNotNull('group_id');
    }

    // Accessors
    public function getIsReadAttribute(): bool
    {
        return $this->read_at !== null;
    }

    public function getIsUnreadAttribute(): bool
    {
        return $this->read_at === null;
    }

    public function getHasAttachmentsAttribute(): bool
    {
        return !empty($this->attachments);
    }
}

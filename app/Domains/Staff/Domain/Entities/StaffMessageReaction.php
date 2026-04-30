<?php

declare(strict_types=1);

namespace App\Domains\Staff\Domain\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * StaffMessageReaction — реакция на сообщение.
 * CatVRF 2026 — PRODUCTION MANDATORY.
 */
final class StaffMessageReaction extends Model
{
    protected $table = 'staff_message_reactions';

    protected $fillable = [
        'tenant_id',
        'staff_id',
        'reactionable_id',
        'reactionable_type',
        'emoji',
    ];

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Tenant::class);
    }

    public function reactionable(): MorphTo
    {
        return $this->morphTo();
    }
}

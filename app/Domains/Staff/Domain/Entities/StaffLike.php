<?php

declare(strict_types=1);

namespace App\Domains\Staff\Domain\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * StaffLike — лайк (реакция) на пост или комментарий.
 * CatVRF 2026 — PRODUCTION MANDATORY.
 */
final class StaffLike extends Model
{
    protected $table = 'staff_likes';

    protected $fillable = [
        'tenant_id',
        'staff_id',
        'likeable_id',
        'likeable_type',
    ];

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Tenant::class);
    }

    public function likeable(): MorphTo
    {
        return $this->morphTo();
    }
}

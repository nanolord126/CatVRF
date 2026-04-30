<?php

declare(strict_types=1);

namespace App\Domains\Staff\Domain\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * StaffAnnouncementRead — отметка о прочтении объявления.
 * CatVRF 2026 — PRODUCTION MANDATORY.
 */
final class StaffAnnouncementRead extends Model
{
    protected $table = 'staff_announcement_reads';

    protected $fillable = [
        'tenant_id',
        'staff_id',
        'announcement_id',
        'read_at',
    ];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    public function announcement(): BelongsTo
    {
        return $this->belongsTo(StaffAnnouncement::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Tenant::class);
    }
}

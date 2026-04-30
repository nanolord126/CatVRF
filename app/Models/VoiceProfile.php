<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class VoiceProfile extends Model
{
    protected $fillable = [
        'tenant_id',
        'user_id',
        'templates',
        'sample_count',
        'enrollment_status',
        'enrolled_at',
        'last_enrolled_at',
        'last_verified_at',
        'verification_count',
        'success_rate',
    ];

    protected $casts = [
        'templates' => 'json',
        'sample_count' => 'int',
        'enrolled_at' => 'datetime',
        'last_enrolled_at' => 'datetime',
        'last_verified_at' => 'datetime',
        'verification_count' => 'int',
        'success_rate' => 'int',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function ($query) {
            if (app()->bound('tenant') && app('tenant') instanceof \App\Models\Tenant) {
                $query->where('tenant_id', app('tenant')->id);
            }
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Tenant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeEnrolled($query)
    {
        return $query->where('enrollment_status', 'completed');
    }

    public function scopeInProgress($query)
    {
        return $query->where('enrollment_status', 'in_progress');
    }

    public function isEnrolled(): bool
    {
        return $this->enrollment_status === 'completed';
    }

    public function needsMoreSamples(): bool
    {
        return $this->sample_count < 3;
    }
}

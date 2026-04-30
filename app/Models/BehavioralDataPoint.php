<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class BehavioralDataPoint extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'tenant_id',
        'session_id',
        'user_id',
        'action',
        'typing_pattern',
        'mouse_dynamics',
        'touch_gestures',
        'ip_address',
        'user_agent',
        'device_fingerprint',
        'collected_at',
        'expires_at',
    ];

    protected $casts = [
        'typing_pattern' => 'json',
        'mouse_dynamics' => 'json',
        'touch_gestures' => 'json',
        'device_fingerprint' => 'json',
        'collected_at' => 'datetime',
        'expires_at' => 'datetime',
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

    public function scopeForSession($query, string $sessionId)
    {
        return $query->where('session_id', $sessionId);
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeRecent($query, int $minutes = 30)
    {
        return $query->where('collected_at', '>', CarbonImmutable::now()->subMinutes($minutes));
    }

    public function scopeNotExpired($query)
    {
        return $query->where(function ($query) {
            $query->whereNull('expires_at')
                ->orWhere('expires_at', '>', CarbonImmutable::now());
        });
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }
}

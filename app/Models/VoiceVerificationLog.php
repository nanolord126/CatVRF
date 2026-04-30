<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class VoiceVerificationLog extends Model
{
    protected $fillable = [
        'tenant_id',
        'voice_profile_id',
        'user_id',
        'similarity_score',
        'verified',
        'verification_duration',
        'quality_score',
        'anti_spoofing_passed',
        'verified_at',
        'correlation_id',
    ];

    protected $casts = [
        'similarity_score' => 'int',
        'verified' => 'bool',
        'verification_duration' => 'float',
        'quality_score' => 'float',
        'anti_spoofing_passed' => 'bool',
        'verified_at' => 'datetime',
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

    public function voiceProfile(): BelongsTo
    {
        return $this->belongsTo(VoiceProfile::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeSuccessful($query)
    {
        return $query->where('verified', true);
    }

    public function scopeFailed($query)
    {
        return $query->where('verified', false);
    }

    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('verified_at', '>=', CarbonImmutable::now()->subDays($days));
    }

    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }
}

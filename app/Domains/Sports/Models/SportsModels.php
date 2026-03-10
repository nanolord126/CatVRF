<?php

namespace App\Domains\Sports\Models;

use App\Traits\Common\HasEcosystemFeatures;
use App\Traits\Common\HasEcosystemAuth;
use App\Traits\Common\HasEcosystemMedia;
use App\Contracts\Common\AIEnableEcosystemEntity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Gym extends Model implements AIEnableEcosystemEntity, HasMedia
{
    use HasEcosystemFeatures, HasEcosystemAuth, HasEcosystemMedia, InteractsWithMedia;

    public function getAiAdjustedPrice(): float { return 0.0; }
    public function getTrustScore(): int { return 98; }
    public function generateAiChecklist(): array { return ['Sanitize equipment', 'Check water supply']; }

    protected $guarded = [];

    protected $casts = [
        'amenities' => 'array',
        'is_active' => 'boolean',
        'operating_hours' => 'array',
    ];

    public function memberships(): HasMany
    {
        return $this->hasMany(GymMembership::class, 'gym_id');
    }

    public function attendanceLogs(): HasMany
    {
        return $this->hasMany(GymAttendanceLog::class, 'gym_id');
    }
}

class GymMembership extends Model
{
    use HasEcosystemFeatures;

    protected $guarded = [];

    protected $casts = [
        'price' => 'decimal:2',
        'duration_months' => 'integer',
        'is_active' => 'boolean',
        'features' => 'array',
    ];

    public function gym(): BelongsTo
    {
        return $this->belongsTo(Gym::class, 'gym_id');
    }

    public function holders(): HasMany
    {
        return $this->hasMany(GymMembershipHolder::class, 'membership_id');
    }
}

class GymMembershipHolder extends Model
{
    use HasEcosystemFeatures;

    protected $guarded = [];

    protected $casts = [
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
        'paid_amount' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

    public function membership(): BelongsTo
    {
        return $this->belongsTo(GymMembership::class, 'membership_id');
    }
}

class GymAttendanceLog extends Model
{
    protected $guarded = [];

    protected $casts = [
        'checked_at' => 'datetime',
        'is_checkout' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

    public function gym(): BelongsTo
    {
        return $this->belongsTo(Gym::class, 'gym_id');
    }
}

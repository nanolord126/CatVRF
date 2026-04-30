<?php

declare(strict_types=1);

namespace Modules\Restaurant\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

final class LoyaltyProgram extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'restaurant_loyalty_programs';

    protected $fillable = [
        'tenant_id',
        'business_group_id',
        'restaurant_id',
        'uuid',
        'name',
        'description',
        'is_active',
        'points_per_ruble',
        'ruble_per_point',
        'signup_bonus',
        'birthday_bonus',
        'tiers',
        'happy_hours_enabled',
        'happy_hours_config',
        'double_points_enabled',
        'double_points_config',
        'starts_at',
        'ends_at',
        'metadata',
        'correlation_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'points_per_ruble' => 'integer',
        'ruble_per_point' => 'integer',
        'signup_bonus' => 'integer',
        'birthday_bonus' => 'integer',
        'tiers' => 'json',
        'happy_hours_enabled' => 'boolean',
        'happy_hours_config' => 'json',
        'double_points_enabled' => 'boolean',
        'double_points_config' => 'json',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'metadata' => 'json',
    ];

    protected $hidden = ['deleted_at'];

    // ========================
    // RELATIONSHIPS
    // ========================

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class, 'restaurant_id');
    }

    public function users(): HasMany
    {
        return $this->hasMany(\App\Models\User::class, 'loyalty_program_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(LoyaltyTransaction::class, 'loyalty_program_id');
    }

    // ========================
    // SCOPES
    // ========================

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }

    // ========================
    // METHODS
    // ========================

    public function calculatePoints(float $amount): int
    {
        $points = (int) floor($amount * $this->points_per_ruble);

        if ($this->double_points_enabled && $this->isDoublePointsTime()) {
            $points *= 2;
        }

        return $points;
    }

    public function calculatePointsValue(int $points): float
    {
        return $points * $this->ruble_per_point;
    }

    public function isDoublePointsTime(): bool
    {
        if (!$this->double_points_enabled || !$this->double_points_config) {
            return false;
        }

        $config = $this->double_points_config;
        $now = now();

        if (!empty($config['days']) && !in_array($now->dayOfWeek, $config['days'])) {
            return false;
        }

        if (!empty($config['start_time']) && !empty($config['end_time'])) {
            $startTime = now()->setTimeFromTimeString($config['start_time']);
            $endTime = now()->setTimeFromTimeString($config['end_time']);

            if ($now->lt($startTime) || $now->gt($endTime)) {
                return false;
            }
        }

        return true;
    }

    public function getTierThreshold(string $tier): ?int
    {
        if (!$this->tiers) {
            return null;
        }

        return $this->tiers[$tier]['threshold'] ?? null;
    }

    public function getTierMultiplier(string $tier): float
    {
        if (!$this->tiers) {
            return 1.0;
        }

        return $this->tiers[$tier]['multiplier'] ?? 1.0;
    }

    public function activate(): bool
    {
        $this->is_active = true;
        return $this->save();
    }

    public function deactivate(): bool
    {
        $this->is_active = false;
        return $this->save();
    }

    protected static function booted(): void
    {
        self::addGlobalScope('tenant_scoped', function ($query) {
            if ($tenantId = tenant('id')) {
                $query->where('tenant_id', $tenantId);
            }
        });

        self::creating(function ($model) {
            if (!$model->uuid) {
                $model->uuid = Str::uuid()->toString();
            }

            // Default tiers config
            if (!$model->tiers) {
                $model->tiers = [
                    'bronze' => ['threshold' => 1000, 'bonus_multiplier' => 1.0],
                    'silver' => ['threshold' => 5000, 'bonus_multiplier' => 1.2],
                    'gold' => ['threshold' => 20000, 'bonus_multiplier' => 1.5],
                    'platinum' => ['threshold' => 50000, 'bonus_multiplier' => 2.0],
                ];
            }
        });
    }
}

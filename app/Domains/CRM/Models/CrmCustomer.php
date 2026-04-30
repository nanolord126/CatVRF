<?php

declare(strict_types=1);

namespace App\Domains\CRM\Models;

use App\Traits\TenantScoped;
use App\Models\Tenant;
use App\Models\BusinessGroup;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Carbon\CarbonImmutable;

/**
 * CrmCustomer — клиент в CRM.
 * Универсальная модель для всех вертикалей с вертикаль-специфичными профилями.
 * Канон CatVRF 2026 — PRODUCTION MANDATORY.
 */
final class CrmCustomer extends Model
{
    use TenantScoped, SoftDeletes;

    protected $table = 'crm_customers';

    protected $fillable = [
        'tenant_id',
        'business_group_id',
        'user_id',
        'uuid',
        'type',
        'first_name',
        'last_name',
        'middle_name',
        'company_name',
        'inn',
        'email',
        'phone',
        'address',
        'city',
        'country',
        'postal_code',
        'birth_date',
        'gender',
        'avatar',
        'source',
        'loyalty_tier',
        'is_vip',
        'total_spent',
        'orders_count',
        'last_order_at',
        'last_interaction_at',
        'is_blocked',
        'block_reason',
        'notes',
        'preferences',
        'communication_preferences',
        'metadata',
        'correlation_id',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'last_order_at' => 'datetime',
        'last_interaction_at' => 'datetime',
        'is_vip' => 'boolean',
        'is_blocked' => 'boolean',
        'total_spent' => 'integer',
        'orders_count' => 'integer',
        'preferences' => 'json',
        'communication_preferences' => 'json',
        'metadata' => 'json',
    ];

    public function scopeIndividual(Builder $query): Builder
    {
        return $query->where('type', 'individual');
    }

    public function scopeBusiness(Builder $query): Builder
    {
        return $query->where('type', 'business');
    }

    public function scopeVip(Builder $query): Builder
    {
        return $query->where('is_vip', true);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_blocked', false);
    }

    public function scopeBlocked(Builder $query): Builder
    {
        return $query->where('is_blocked', true);
    }

    public function scopeByLoyaltyTier(Builder $query, string $tier): Builder
    {
        return $query->where('loyalty_tier', $tier);
    }

    public function scopeRecent(Builder $query, int $days = 30): Builder
    {
        return $query->where('last_interaction_at', '>=', CarbonImmutable::now()->subDays($days));
    }

    public function scopeSleeping(Builder $query, int $daysInactive = 90): Builder
    {
        return $query->where('last_interaction_at', '<', CarbonImmutable::now()->subDays($daysInactive));
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    public function businessGroup(): BelongsTo
    {
        return $this->belongsTo(BusinessGroup::class, 'business_group_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function deals(): HasMany
    {
        return $this->hasMany(CrmDeal::class, 'customer_id');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(CrmTask::class, 'customer_id');
    }

    public function getFullNameAttribute(): string
    {
        if ($this->company_name) {
            return $this->company_name;
        }

        return trim(($this->first_name ?? '').' '.($this->last_name ?? '').' '.($this->middle_name ?? ''));
    }

    public function incrementSpent(int $amount): void
    {
        $this->increment('total_spent', $amount);
        $this->increment('orders_count');
        $this->update(['last_order_at' => CarbonImmutable::now()]);
        $this->updateLoyaltyTier();
    }

    public function updateLoyaltyTier(): void
    {
        $spent = $this->total_spent;

        $tier = match (true) {
            $spent >= 1000000 => 'diamond',
            $spent >= 500000 => 'platinum',
            $spent >= 200000 => 'gold',
            $spent >= 50000 => 'silver',
            default => 'bronze',
        };

        $this->update(['loyalty_tier' => $tier]);
    }

    protected static function booted(): void
    {
        static::creating(function (self $model): void {
            if (! $model->uuid) {
                $model->uuid = Str::uuid()->toString();
            }
        });
    }
}

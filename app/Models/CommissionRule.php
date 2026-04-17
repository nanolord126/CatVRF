<?php declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

/**
 * Модель правил комиссий.
 * 
 * Канон 2026: B2C = 14%, B2B = tier-based (8-12%)
 * 
 * @property int $id
 * @property string $uuid
 * @property int $tenant_id
 * @property int|null $business_group_id
 * @property string $name
 * @property string $type
 * @property string $entity_type
 * @property float $b2c_rate
 * @property float $b2b_rate
 * @property array|null $b2b_tiers
 * @property float|null $fixed_amount
 * @property float|null $min_amount
 * @property float|null $max_amount
 * @property bool $is_active
 * @property \Carbon\Carbon|null $valid_from
 * @property \Carbon\Carbon|null $valid_until
 * @property string|null $correlation_id
 * @property array|null $tags
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
final class CommissionRule extends Model
{
    protected $table = 'commission_rules';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'business_group_id',
        'name',
        'type',
        'entity_type',
        'b2c_rate',
        'b2b_rate',
        'b2b_tiers',
        'fixed_amount',
        'min_amount',
        'max_amount',
        'is_active',
        'valid_from',
        'valid_until',
        'correlation_id',
        'tags',
    ];

    protected $casts = [
        'b2c_rate' => 'decimal:2',
        'b2b_rate' => 'decimal:2',
        'b2b_tiers' => 'json',
        'fixed_amount' => 'decimal:2',
        'min_amount' => 'decimal:2',
        'max_amount' => 'decimal:2',
        'is_active' => 'boolean',
        'valid_from' => 'datetime',
        'valid_until' => 'datetime',
        'tags' => 'json',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', static function (Builder $builder): void {
            if (function_exists('tenant') && tenant()?->id) {
                $builder->where('tenant_id', tenant()?->id);
            }
        });

        static::addGlobalScope('businessGroup', static function (Builder $builder): void {
            if (function_exists('tenant') && tenant()?->business_group_id) {
                $builder->where('business_group_id', tenant()?->business_group_id);
            }
        });

        static::creating(static function (self $model): void {
            if (empty($model->uuid)) {
                $model->uuid = \Illuminate\Support\Str::uuid()->toString();
            }
        });
    }

    /**
     * Scope: только активные правила
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('valid_from')
                    ->orWhere('valid_from', '<=', now());
            })
            ->where(function ($query) {
                $query->whereNull('valid_until')
                    ->orWhere('valid_until', '>=', now());
            });
    }

    /**
     * Scope: правила для конкретной сущности (вертикали)
     */
    public function scopeForEntity(Builder $query, string $entityType): Builder
    {
        return $query->where('entity_type', $entityType);
    }
}

<?php

declare(strict_types=1);

namespace App\Domains\Hotels\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * B2BContract — модель B2B-контракта с отелем CatVRF 2026.
 *
 * Хранит данные о договорах между бизнес-группами и отелями:
 * скидки, условия, валидность.
 *
 * @package CatVRF
 * @version 2026.1
 * @see https://catvrf.ru/docs/b2bcontract
 */
final class B2BContract extends Model
{

    protected $table = 'hotel_b2b_contracts';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'business_group_id',
        'hotel_id',
        'name',
        'discount_percent',
        'is_active',
        'is_valid',
        'contract_data',
        'correlation_id',
    ];

    protected $casts = [
        'discount_percent' => 'integer',
        'is_active' => 'boolean',
        'is_valid' => 'boolean',
        'contract_data' => 'json',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model): void {
            if (empty($model->uuid)) {
                $model->uuid = Str::uuid()->toString();
            }
        });

        static::addGlobalScope('tenant', function ($builder): void {
            $builder->where('hotel_b2b_contracts.tenant_id', tenant()->id);
        });
    }

    public function hotel(): BelongsTo
    {
        return $this->belongsTo(Hotel::class, 'hotel_id');
    }

    public function businessGroup(): BelongsTo
    {
        return $this->belongsTo(\App\Models\BusinessGroup::class, 'business_group_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Tenant::class, 'tenant_id');
    }

    /**
     * Проверить валидность контракта.
     */
    public function isValid(): bool
    {
        return $this->is_active && $this->is_valid;
    }

    /**
     * Получить строковое представление модели.
     */
    public function __toString(): string
    {
        return sprintf(
            '%s[id=%s, hotel=%s, discount=%d%%]',
            static::class,
            $this->id ?? 'new',
            $this->hotel_id ?? 'N/A',
            $this->discount_percent ?? 0,
        );
    }
}

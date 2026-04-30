<?php

declare(strict_types=1);

namespace App\Domains\Travel\SubVerticals\Hotels\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use App\Models\BusinessGroup;
use App\Models\Tenant;

/**
 * B2BContract — модель B2B-контракта с отелем CatVRF 2026.
 *
 * Хранит данные о договорах между бизнес-группами и отелями:
 * скидки, условия, валидность.
 *
 * @version 2026.1
 *
 * @see https://catvrf.ru/docs/b2bcontract
 */
final class B2BContract extends Model
{
    use TenantScoped;

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

    public function hotel(): BelongsTo
    {
        return $this->belongsTo(Hotel::class, 'hotel_id');
    }

    public function businessGroup(): BelongsTo
    {
        return $this->belongsTo(BusinessGroup::class, 'business_group_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
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
            self::class,
            $this->id ?? 'new',
            $this->hotel_id ?? 'N/A',
            $this->discount_percent ?? 0,
        );
    }

    protected static function booted(): void
    {
        self::creating(function (self $model): void {
            if (empty($model->uuid)) {
                $model->uuid = Str::uuid()->toString();
            }
        });

        self::addGlobalScope('tenant', function ($builder): void {
            $builder->where('hotel_b2b_contracts.tenant_id', tenant()->id);
        });
    }
}

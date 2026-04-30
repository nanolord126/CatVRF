<?php

declare(strict_types=1);

namespace App\Domains\Payment\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\BusinessGroup;
use App\Models\Tenant;

/**
 * Правило комиссии для вертикали/субвертикали.
 *
 * Определяет процент комиссии платформы для конкретной вертикали.
 * Может быть переопределено на уровне бизнес-группы.
 *
 * @property int $id
 * @property int $tenant_id
 * @property int|null $business_group_id
 * @property string $vertical_code
 * @property string|null $sub_vertical_code
 * @property float $commission_percent
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $valid_from
 * @property \Illuminate\Support\Carbon|null $valid_until
 */
final class CommissionRule extends Model
{
    use TenantScoped;

    protected $table = 'commission_rules';

    protected $fillable = [
        'tenant_id',
        'business_group_id',
        'vertical_code',
        'sub_vertical_code',
        'commission_percent',
        'is_active',
        'valid_from',
        'valid_until',
    ];

    protected $casts = [
        'commission_percent' => 'decimal:2',
        'is_active' => 'boolean',
        'valid_from' => 'datetime',
        'valid_until' => 'datetime',
    ];

    /**
     * @return BelongsTo<Tenant, self>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    /**
     * @return BelongsTo<BusinessGroup, self>
     */
    public function businessGroup(): BelongsTo
    {
        return $this->belongsTo(BusinessGroup::class, 'business_group_id');
    }

    /**
     * Получить активное правило для вертикали.
     */
    public static function getActiveRule(
        string $verticalCode,
        ?string $subVerticalCode = null,
        ?int $businessGroupId = null,
    ): ?self {
        $query = self::where('vertical_code', $verticalCode)
            ->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('valid_from')
                    ->orWhere('valid_from', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('valid_until')
                    ->orWhere('valid_until', '>=', now());
            });

        if ($subVerticalCode) {
            $query->where('sub_vertical_code', $subVerticalCode);
        } else {
            $query->whereNull('sub_vertical_code');
        }

        if ($businessGroupId) {
            $query->where('business_group_id', $businessGroupId);
        } else {
            $query->whereNull('business_group_id');
        }

        return $query->first();
    }
}

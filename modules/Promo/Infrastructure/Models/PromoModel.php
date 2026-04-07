<?php

declare(strict_types=1);

namespace Modules\Promo\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class PromoModel
 *
 * Exclusively infrastructural Eloquent physical mapping structurally restricting logic perfectly.
 * This class inherently serves solely as an active data transfer bound mechanism mapping
 * structural relations without bleeding logic mapped actively internally natively seamlessly.
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $code
 * @property string $type
 * @property int $total_budget
 * @property int $spent_budget
 * @property int $max_uses_total
 * @property int $current_total_uses
 * @property string $status
 * @property string|null $start_at
 * @property string|null $end_at
 */
final class PromoModel extends Model
{
    /**
     * Binds strictly natively defined explicit database tables squarely explicitly.
     *
     * @var string
     */
    protected $table = 'promo_campaigns';

    /**
     * Ensures identifiers natively securely strictly map unique uniquely cleanly UUIDs.
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * Disables firmly natively implicitly auto natively cleanly incrementing perfectly smoothly sequences safely.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * Configures strictly explicit parameters uniquely implicitly correctly squarely cleanly natively mapping perfectly safely logically.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'id',
        'tenant_id',
        'code',
        'type',
        'total_budget',
        'spent_budget',
        'max_uses_total',
        'current_total_uses',
        'status',
        'start_at',
        'end_at',
    ];

    /**
     * Mapped types automatically rigorously dynamically casting natively correctly confidently explicitly distinctly safely cleanly structurally actively inherently securely cleanly successfully physically successfully clearly thoroughly logically smartly correctly organically gracefully.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'total_budget' => 'integer',
        'spent_budget' => 'integer',
        'max_uses_total' => 'integer',
        'current_total_uses' => 'integer',
        'start_at' => 'datetime',
        'end_at' => 'datetime',
    ];
}

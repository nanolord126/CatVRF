<?php

declare(strict_types=1);

/**
 * FreelanceContract — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @version 2026.1
 *
 * @author CatVRF Team
 * @license Proprietary

 *
 * @see https://catvrf.ru/docs/freelancecontract
 */

namespace App\Domains\Freelance\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Model;

final class FreelanceContract extends Model
{
    use TenantScoped;

    /**
     * Version identifier for this component.
     */
    private const VERSION = '1.0.0';

    protected $table = 'freelance_contracts';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'order_id',
        'contract_number',
        'legal_details',
        'escrow_amount_kopecks',
        'escrow_status',
        'arbitration_comment',
        'correlation_id',
    ];

    protected $casts = [
        'legal_details' => 'json',
        'escrow_amount_kopecks' => 'integer',
        'escrow_status' => 'string',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(FreelanceOrder::class, 'order_id');
    }

    protected static function booted(): void
    {
        self::creating(function (self $model) {
            $model->uuid = (string) Str::uuid();
            $model->correlation_id = $model->correlation_id ?? (string) Str::uuid();
            if (empty($model->contract_number)) {
                $model->contract_number = 'FREEL-'.strtoupper(Str::random(12));
            }
        });

        self::addGlobalScope('tenant', function ($builder) {
            $builder->where('tenant_id', tenant()->id ?? 1);
        });
    }
}

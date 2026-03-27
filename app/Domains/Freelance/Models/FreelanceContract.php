<?php

declare(strict_types=1);

namespace App\Domains\Freelance\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * КАНОН 2026 — FREELANCE CONTRACT (ESCROW)
 * Эскроу-контракт сделки (Безопасная сделка).
 */
final class FreelanceContract extends Model
{
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

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            $model->uuid = (string) Str::uuid();
            $model->correlation_id = $model->correlation_id ?? (string) Str::uuid();
            if (empty($model->contract_number)) {
                $model->contract_number = 'FREEL-' . strtoupper(Str::random(12));
            }
        });

        static::addGlobalScope('tenant', function ($builder) {
            $builder->where('tenant_id', tenant()->id ?? 1);
        });
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(FreelanceOrder::class, 'order_id');
    }
}

<?php

declare(strict_types=1);

namespace App\Domains\Education\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * Model CorporateContract.
 * Канон 2026: B2B, slots_count, tenant_id, correlation_id.
 */
final class CorporateContract extends Model
{
    protected $table = 'corporate_contracts';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'provider_tenant_id',
        'contract_number',
        'total_amount_kopecks',
        'slots_count',
        'used_slots_count',
        'starts_at',
        'expires_at',
        'status',
        'correlation_id',
        'metadata',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
        'total_amount_kopecks' => 'integer',
        'slots_count' => 'integer',
        'used_slots_count' => 'integer',
        'metadata' => 'json',
    ];

    /**
     * КАНОН 2026: Изоляция тенанта (B2B компания видит только свои контракты)
     */
    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function (Builder $builder) {
            if (auth()->check()) {
                // ТЕНЕНТ может быть либо плательщиком (B2B клиент), либо провайдером
                $tid = auth()->user()->tenant_id;
                $builder->where(function($q) use ($tid) {
                    $q->where('tenant_id', $tid)
                      ->orWhere('provider_tenant_id', $tid);
                });
            }
        });

        static::creating(function (CorporateContract $contract) {
            $contract->uuid = $contract->uuid ?? (string) Str::uuid();
            $contract->correlation_id = $contract->correlation_id ?? (string) Str::uuid();
        });
    }

    /**
     * Провайдер обучения.
     */
    public function provider(): BelongsTo
    {
        // В реальной системе это связь с Tenant моделью
        return $this->belongsTo(\App\Models\Tenant::class, 'provider_tenant_id');
    }
}

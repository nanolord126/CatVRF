<?php

declare(strict_types=1);

namespace App\Domains\Education\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use App\Models\Tenant;

final class CorporateContract extends Model
{
    use TenantScoped;

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
     * Провайдер обучения.
     */
    public function provider(): BelongsTo
    {
        // В реальной системе это связь с Tenant моделью
        return $this->belongsTo(Tenant::class, 'provider_tenant_id');
    }

    /**
     * КАНОН 2026: Изоляция тенанта (B2B компания видит только свои контракты)
     */
    protected static function booted(): void
    {
        self::addGlobalScope('tenant', function (Builder $builder) {
            if (function_exists('tenant') && tenant()) {
                // ТЕНЕНТ может быть либо плательщиком (B2B клиент), либо провайдером
                $tid = tenant()->id;
                $builder->where(function ($q) use ($tid) {
                    $q->where('tenant_id', $tid)
                        ->orWhere('provider_tenant_id', $tid);
                });
            }
        });

        self::creating(function (CorporateContract $contract) {
            $contract->uuid = $contract->uuid ?? (string) Str::uuid();
            $contract->correlation_id = $contract->correlation_id ?? (string) Str::uuid();
        });
    }
}

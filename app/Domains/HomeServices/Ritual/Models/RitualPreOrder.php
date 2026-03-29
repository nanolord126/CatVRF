<?php

declare(strict_types=1);

namespace App\Domains\RitualServices\RitualServices\Ritual\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * RitualPreOrder Model — Production Ready 2026
 * 
 * Прижизненный контракт на ритуальные услуги.
 * Требует высочайшей конфиденциальности и строгого учета.
 * 
 * @property string $uuid
 * @property int $tenant_id
 * @property int $client_id
 * @property string $contract_number
 * @property array $selected_plan
 * @property int $accumulated_amount_kopecks
 */
final class RitualPreOrder extends Model
{
    use SoftDeletes;

    protected $table = 'ritual_pre_orders';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'client_id',
        'contract_number',
        'person_beneficiary_name',
        'selected_plan',
        'accumulated_amount_kopecks',
        'target_amount_kopecks',
        'is_active',
        'correlation_id',
    ];

    protected $hidden = [
        'id',
        'deleted_at',
    ];

    protected $casts = [
        'selected_plan' => 'json',
        'accumulated_amount_kopecks' => 'integer',
        'target_amount_kopecks' => 'integer',
        'is_active' => 'boolean',
        'tenant_id' => 'integer',
    ];

    /**
     * Booted method for global scoping and data protection.
     */
    protected static function booted(): void
    {
        // Изоляция данных на уровне базы (Tenant Scoping)
        static::addGlobalScope('tenant', function (Builder $builder) {
            if (function_exists('tenant') && tenant('id')) {
                $builder->where('tenant_id', tenant('id'));
            }
        });

        // Автогенерация UUID и Correlation ID
        static::creating(function (RitualPreOrder $model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
            if (empty($model->correlation_id)) {
                $model->correlation_id = (string) Str::uuid();
            }
            if (empty($model->tenant_id) && function_exists('tenant')) {
                $model->tenant_id = (int) tenant('id');
            }
        });
    }

    /**
     * Владелец контракта.
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'client_id');
    }

    /**
     * Поверка достаточности накоплений для активации контракта.
     */
    public function canActivate(): bool
    {
        return $this->accumulated_amount_kopecks >= $this->target_amount_kopecks;
    }
}

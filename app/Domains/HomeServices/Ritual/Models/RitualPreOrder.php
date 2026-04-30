<?php

declare(strict_types=1);

namespace App\Domains\HomeServices\Ritual\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

final class RitualPreOrder extends Model
{
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
     * Владелец контракта.
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    /**
     * Поверка достаточности накоплений для активации контракта.
     */
    public function canActivate(): bool
    {
        return $this->accumulated_amount_kopecks >= $this->target_amount_kopecks;
    }

    /**
     * Booted method for global scoping and data protection.
     */
    protected static function booted_disabled(): void
    {
        // Изоляция данных на уровне базы (Tenant Scoping)
        self::addGlobalScope('tenant', function (Builder $builder) {
            if (function_exists('tenant') && tenant()->id) {
                $builder->where('tenant_id', tenant()->id);
            }
        });

        // Автогенерация UUID и Correlation ID
        self::creating(function (RitualPreOrder $model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
            if (empty($model->correlation_id)) {
                $model->correlation_id = (string) Str::uuid();
            }
            if (empty($model->tenant_id) && function_exists('tenant')) {
                $model->tenant_id = (int) tenant()->id;
            }
        });
    }
}

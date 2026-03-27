<?php

declare(strict_types=1);

namespace App\Domains\RitualServices\RitualServices\Ritual\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * FuneralOrder Model — Production Ready 2026
 * 
 * Комплексный заказ на организацию похорон.
 * Реализовано по доменному канону 2026: UUID, Correlation ID, Tenant Scope.
 * 
 * @property string $uuid
 * @property int $tenant_id
 * @property int $agency_id
 * @property int $client_id
 * @property string $status
 * @property int $total_amount_kopecks
 * @property bool $is_installment
 */
final class FuneralOrder extends Model
{
    use SoftDeletes;

    protected $table = 'ritual_funeral_orders';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'agency_id',
        'client_id',
        'deceased_name',
        'death_date',
        'funeral_date',
        'burial_location',
        'status',
        'total_amount_kopecks',
        'paid_amount_kopecks',
        'selected_services',
        'is_installment',
        'correlation_id',
    ];

    protected $hidden = [
        'id',
        'deleted_at',
    ];

    protected $casts = [
        'selected_services' => 'json',
        'total_amount_kopecks' => 'integer',
        'paid_amount_kopecks' => 'integer',
        'is_installment' => 'boolean',
        'death_date' => 'date',
        'funeral_date' => 'datetime',
        'tenant_id' => 'integer',
    ];

    /**
     * Booted method for global scoping and UUID generation.
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
        static::creating(function (FuneralOrder $model) {
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
     * Агентство, курирующее организацию.
     */
    public function agency(): BelongsTo
    {
        return $this->belongsTo(RitualAgency::class, 'agency_id');
    }

    /**
     * Клиент (Заказчик).
     */
    public function client(): BelongsTo
    {
        /** @var \App\Models\User $userModel */
        return $this->belongsTo(\App\Models\User::class, 'client_id');
    }

    /**
     * Определить полноту оплаты заказа.
     */
    public function isFullyPaid(): bool
    {
        return $this->paid_amount_kopecks >= $this->total_amount_kopecks;
    }
}

<?php

declare(strict_types=1);

namespace App\Domains\Vapes\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * VapeOrder Model — Production Ready 2026
 * 
 * Заказ на вейп-продукцию.
 * Строгая привязка к подтвержденной верификации возраста (18+).
 * Интеграция с сессиями Честного ЗНАКа.
 * 
 * @property string $uuid
 * @property int $tenant_id
 * @property int $user_id
 * @property int|null $age_verification_id
 * @property string $status draft, age_pending, paid, shipped, delivered, cancelled
 * @property bigInteger $total_amount_kopecks
 * @property array $items
 * @property string $marking_session_id (Session ID Честного ЗНАКа)
 */
final class VapeOrder extends Model
{
    use SoftDeletes;

    protected $table = 'vapes_orders';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'user_id',
        'age_verification_id',
        'status',
        'total_amount_kopecks',
        'items',
        'marking_session_id',
        'correlation_id',
    ];

    protected $casts = [
        'items' => 'json',
        'total_amount_kopecks' => 'integer',
        'tenant_id' => 'integer',
        'user_id' => 'integer',
        'age_verification_id' => 'integer',
    ];

    protected $hidden = [
        'id',
        'deleted_at',
    ];

    /**
     * Booted method for global scoping and data protection.
     */
    protected static function booted(): void
    {
        // Изоляция данных на уровне базы (Tenant Scoping Канон 2026)
        static::addGlobalScope('tenant', function (Builder $builder) {
            if (function_exists('tenant') && tenant('id')) {
                $builder->where('tenant_id', (int) tenant('id'));
            }
        });

        // Автогенерация UUID и Correlation ID
        static::creating(function (VapeOrder $model) {
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
     * Заказчик вейп-продукции.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

    /**
     * Верификация возраста, привязанная к заказу.
     */
    public function ageVerification(): BelongsTo
    {
        return $this->belongsTo(VapeAgeVerification::class, 'age_verification_id');
    }

    /**
     * Проверка: пройдена ли возрастная проверка для этого заказа.
     */
    public function isAgeVerified(): bool
    {
        return $this->ageVerification?->status === 'verified';
    }

    /**
     * Проверка: содержит ли заказ маркированную продукцию ("Честный ЗНАК").
     */
    public function needsMarkingCheck(): bool
    {
        if (empty($this->items)) return false;
        
        foreach ($this->items as $item) {
            if (!empty($item['requires_marking'])) {
                return true;
            }
        }
        return false;
    }
}

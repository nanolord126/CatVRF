<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * Appointment — Eloquent-модель записи (бронирования) к мастеру.
 *
 * Tenant-aware, business_group_id scoping.
 * Отслеживает статус (pending → confirmed → completed / cancelled).
 *
 * @property int $id
 * @property string $uuid
 * @property int $tenant_id
 * @property int|null $business_group_id
 * @property int $user_id
 * @property int $salon_id
 * @property int $master_id
 * @property int $service_id
 * @property \Carbon\Carbon $datetime_start
 * @property \Carbon\Carbon $datetime_end
 * @property string $status
 * @property int $price
 * @property string|null $payment_status
 * @property string|null $client_comment
 * @property array|null $tags
 * @property string|null $correlation_id
 */
final class Appointment extends Model
{
    use HasFactory;
    use HasUuids;
    use SoftDeletes;

    protected $table = 'beauty_appointments';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'business_group_id',
        'user_id',
        'salon_id',
        'master_id',
        'service_id',
        'datetime_start',
        'datetime_end',
        'status',
        'price',
        'payment_status',
        'client_comment',
        'tags',
        'correlation_id',
    ];

    protected $casts = [
        'datetime_start' => 'datetime',
        'datetime_end' => 'datetime',
        'price' => 'integer',
        'tags' => 'json',
        'deleted_at' => 'datetime',
    ];

    /**
     * CANON 2026: tenant scoping + auto uuid/correlation_id.
     */
    protected static function booted(): void
    {
        static::addGlobalScope('tenant_scoping', static function ($builder): void {
            if (function_exists('tenant') && tenant()->id) {
                $builder->where('tenant_id', tenant()->id);
            }
        });

        static::creating(static function (self $model): void {
            if (empty($model->uuid)) {
                $model->uuid = Str::uuid()->toString();
            }

            if (empty($model->correlation_id)) {
                $model->correlation_id = Str::uuid()->toString();
            }
        });
    }

    /**
     * Салон, в котором создана запись.
     */
    public function salon(): BelongsTo
    {
        return $this->belongsTo(BeautySalon::class, 'salon_id');
    }

    /**
     * Мастер, к которому запись.
     */
    public function master(): BelongsTo
    {
        return $this->belongsTo(Master::class, 'master_id');
    }

    /**
     * Услуга, на которую запись.
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(BeautyService::class, 'service_id');
    }

    /**
     * Пользователь-клиент.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

    /**
     * Тенант.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Tenant::class, 'tenant_id');
    }
}

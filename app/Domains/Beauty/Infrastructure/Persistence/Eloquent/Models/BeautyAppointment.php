<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Infrastructure\Persistence\Eloquent\Models;


use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Eloquent-модель записи на услугу (appointment).
 *
 * Статусы: pending | confirmed | completed | cancelled | no_show
 * payment_status: pending | paid | refunded
 *
 * @property int         $id
 * @property string      $uuid
 * @property int         $tenant_id
 * @property int|null    $business_group_id
 * @property int         $salon_id
 * @property int         $master_id
 * @property int         $service_id
 * @property int         $client_id
 * @property \Carbon\Carbon $start_at
 * @property \Carbon\Carbon $end_at
 * @property int         $price_cents        Цена в копейках
 * @property string      $status
 * @property string      $payment_status
 * @property array|null  $tags
 * @property string|null $correlation_id
 */
final class BeautyAppointment extends Model
{
    use SoftDeletes;

    protected $table = 'beauty_appointments';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'business_group_id',
        'salon_id',
        'master_id',
        'service_id',
        'client_id',
        'start_at',
        'end_at',
        'price_cents',
        'status',
        'payment_status',
        'tags',
        'correlation_id',
    ];

    protected $hidden = [];

    protected $casts = [
        'start_at'       => 'datetime',
        'end_at'         => 'datetime',
        'price_cents'    => 'integer',
        'tags'           => 'json',
    ];

    /**
     * Глобальный scope по tenant_id.
     */
    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function (Builder $query): void {
            if (function_exists('tenant') && tenant()) {
                $query->where('beauty_appointments.tenant_id', tenant()->id);
            }
        });
    }

    // ===== Отношения =====

    public function salon(): BelongsTo
    {
        return $this->belongsTo(BeautySalon::class, 'salon_id', 'id');
    }

    public function master(): BelongsTo
    {
        return $this->belongsTo(BeautyMaster::class, 'master_id', 'id');
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(BeautyService::class, 'service_id', 'id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'client_id', 'id');
    }

    // ===== Scope-запросы =====

    public function scopeConfirmed(Builder $query): Builder
    {
        return $query->where('status', 'confirmed');
    }

    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->where('start_at', '>=', Carbon::now())->orderBy('start_at');
    }
}

<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Eloquent-модель услуги салона красоты.
 *
 * Все суммы в копейках (int). Применяет глобальный scope по tenant_id.
 *
 * @property int         $id
 * @property string      $uuid
 * @property int         $tenant_id
 * @property int         $salon_id
 * @property int|null    $master_id
 * @property string      $name
 * @property string      $category
 * @property int         $price_cents   Цена в копейках
 * @property int         $duration_minutes
 * @property string|null $description
 * @property array|null  $consumables_json  Список расходников и кол-во
 * @property bool        $is_active
 * @property array|null  $tags
 * @property string|null $correlation_id
 */
final class BeautyService extends Model
{
    use SoftDeletes;

    protected $table = 'beauty_services';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'business_group_id',
        'salon_id',
        'master_id',
        'name',
        'category',
        'price_cents',
        'duration_minutes',
        'description',
        'consumables_json',
        'is_active',
        'tags',
        'correlation_id',
    ];

    protected $hidden = [];

    protected $casts = [
        'price_cents'      => 'integer',
        'duration_minutes' => 'integer',
        'consumables_json' => 'json',
        'tags'             => 'json',
        'is_active'        => 'boolean',
    ];

    /**
     * Глобальный scope по tenant_id.
     */
    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function (Builder $query): void {
            if (function_exists('tenant') && tenant()) {
                $query->where('beauty_services.tenant_id', tenant()->id);
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

    public function consumables(): HasMany
    {
        return $this->hasMany(BeautyConsumable::class, 'service_id', 'id');
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(BeautyAppointment::class, 'service_id', 'id');
    }
}

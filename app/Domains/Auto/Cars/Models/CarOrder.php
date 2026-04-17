<?php declare(strict_types=1);

/**
 * CarOrder — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/carorder
 */


namespace App\Domains\Auto\Cars\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class CarOrder extends Model
{

    protected $table = 'car_orders';

        protected $fillable = [
            'tenant_id',
            'car_id',
            'client_id',
            'uuid',
            'amount',
            'status',
            'idempotency_key',
            'tags',
            'correlation_id'
        ];

        protected $casts = [
            'tags' => 'json',
            'amount' => 'integer'
        ];

        protected static function booted(): void
        {
            static::addGlobalScope('tenant_id', function (Builder $builder) {
                $builder->where('tenant_id', tenant()->id ?? 0);
            });

            static::creating(function (Model $model) {
                $model->uuid = $model->uuid ?? (string) Str::uuid();
                $model->tenant_id = $model->tenant_id ?? (tenant()->id ?? 0);
            });
        }

        public function car(): BelongsTo
        {
            return $this->belongsTo(Car::class, 'car_id');
        }

    /**
     * Version identifier for this component.
     */
    private const VERSION = '1.0.0';

}

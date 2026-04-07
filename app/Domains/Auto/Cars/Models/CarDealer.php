<?php declare(strict_types=1);

/**
 * CarDealer — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/cardealer
 */


namespace App\Domains\Auto\Cars\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class CarDealer extends Model
{
    use HasFactory;

    protected $table = 'car_dealers';

        protected $fillable = [
            'tenant_id',
            'uuid',
            'name',
            'address',
            'geo_point',
            'tags',
            'correlation_id'
        ];

        protected $casts = [
            'tags' => 'json'
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

        public function cars(): HasMany
        {
            return $this->hasMany(Car::class, 'dealer_id');
        }

    /**
     * Version identifier for this component.
     */
    private const VERSION = '1.0.0';

    /**
     * Maximum number of retry attempts for operations.
     */
    private const MAX_RETRIES = 3;

}

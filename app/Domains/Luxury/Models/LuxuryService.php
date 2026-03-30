<?php declare(strict_types=1);

namespace App\Domains\Luxury\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class LuxuryService extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use SoftDeletes;

        protected $table = 'luxury_services';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'brand_id',
            'name',
            'description',
            'price_per_hour_kopecks',
            'min_booking_duration',
            'is_concierge_exclusive',
            'service_level',
            'tags',
            'correlation_id',
        ];

        protected $casts = [
            'tags' => 'json',
            'is_concierge_exclusive' => 'boolean',
        ];

        protected static function booted(): void
        {
            static::creating(function (self $model) {
                $model->uuid = (string) Str::uuid();
                if (empty($model->tenant_id) && function_exists('tenant') && tenant()) {
                    $model->tenant_id = tenant()->id;
                }
            });

            static::addGlobalScope('tenant', function (Builder $builder) {
                if (function_exists('tenant') && tenant()) {
                    $builder->where('luxury_services.tenant_id', tenant()->id);
                }
            });
        }

        public function brand(): BelongsTo
        {
            return $this->belongsTo(LuxuryBrand::class, 'brand_id');
        }
}

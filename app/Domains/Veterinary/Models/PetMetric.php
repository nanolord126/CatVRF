<?php declare(strict_types=1);

namespace App\Domains\Veterinary\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class PetMetric extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected $table = 'pet_metrics';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'pet_id',
            'metric_type',
            'value',
            'unit',
            'measured_at',
            'source',
            'correlation_id',
        ];

        protected $casts = [
            'value' => 'float',
            'measured_at' => 'datetime',
        ];

        protected static function booted(): void
        {
            static::creating(function (PetMetric $model) {
                $model->uuid = (string) Str::uuid();
                if (auth()->check() && !$model->tenant_id) {
                    $model->tenant_id = auth()->user()->tenant_id;
                }
            });

            static::addGlobalScope('tenant_id', function ($builder) {
                if (auth()->check()) {
                    $builder->where('tenant_id', auth()->user()->tenant_id);
                }
            });
        }

        public function pet(): BelongsTo
        {
            return $this->belongsTo(Pet::class);
        }
}

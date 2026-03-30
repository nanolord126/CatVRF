<?php declare(strict_types=1);

namespace App\Domains\Beauty\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class BeautyService extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use HasUuids, SoftDeletes;

        protected $table = 'beauty_services';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'salon_id',
            'master_id',
            'name',
            'description',
            'duration_minutes',
            'price',
            'consumables',
            'tags',
            'correlation_id',
        ];

        protected $casts = [
            'duration_minutes' => 'integer',
            'price' => 'integer',
            'consumables' => 'json',
            'tags' => 'json',
            'deleted_at' => 'datetime',
        ];

        protected static function booted(): void
        {
            static::addGlobalScope('tenant_scoping', function ($builder) {
                if (function_exists('tenant') && tenant('id')) {
                    $builder->where('tenant_id', tenant('id'));
                }
            });
        }

        public function salon(): BelongsTo
        {
            return $this->belongsTo(BeautySalon::class, 'salon_id');
        }

        public function master(): BelongsTo
        {
            return $this->belongsTo(Master::class, 'master_id');
        }

        public function appointments(): HasMany
        {
            return $this->hasMany(Appointment::class, 'service_id');
        }
}

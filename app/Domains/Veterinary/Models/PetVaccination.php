<?php declare(strict_types=1);

namespace App\Domains\Veterinary\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class PetVaccination extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use SoftDeletes;

        protected $table = 'pet_vaccinations';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'pet_id',
            'veterinarian_id',
            'vaccine_name',
            'serial_number',
            'vaccinated_at',
            'expires_at',
            'certificate_url',
            'metadata',
            'correlation_id',
        ];

        protected $casts = [
            'vaccinated_at' => 'date',
            'expires_at' => 'date',
            'metadata' => 'json',
        ];

        protected static function booted(): void
        {
            static::creating(function (PetVaccination $model) {
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

        public function veterinarian(): BelongsTo
        {
            return $this->belongsTo(Veterinarian::class);
        }
}

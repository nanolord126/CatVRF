<?php declare(strict_types=1);

namespace App\Domains\Veterinary\Models;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Builder;

final class PetVaccination extends Model
{



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
                if (function_exists('tenant') && tenant() && !$model->tenant_id) {
                    $model->tenant_id = tenant()->id;
                }
            });

            static::addGlobalScope('tenant_id', function (Builder $builder) {
                if (function_exists('tenant') && tenant()) {
                    $builder->where('tenant_id', tenant()->id);
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

<?php declare(strict_types=1);

namespace App\Domains\Veterinary\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

final class Veterinarian extends Model
{
    use HasFactory;

    use SoftDeletes;

        protected $table = 'veterinarians';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'clinic_id',
            'user_id',
            'full_name',
            'specialization',
            'experience_years',
            'bio',
            'rating',
            'tags',
            'correlation_id',
        ];

        protected $casts = [
            'specialization' => 'json',
            'tags' => 'json',
            'experience_years' => 'integer',
            'rating' => 'float',
        ];

        /**
         * Boot logic
         */
        protected static function booted(): void
        {
            static::addGlobalScope('tenant_scope', function (Builder $builder) {
                if (function_exists('tenant') && is_object(tenant()) && isset(tenant()->id)) {
                    $builder->where('veterinarians.tenant_id', tenant()->id);
                }
            });

            static::creating(function (Model $model) {
                $model->uuid = $model->uuid ?? (string) Str::uuid();
                if (function_exists('tenant') && is_object(tenant()) && isset(tenant()->id)) {
                    $model->tenant_id = $model->tenant_id ?? tenant()->id;
                }
            });
        }

        /**
         * Relations: Clinic
         */
        public function clinic(): BelongsTo
        {
            return $this->belongsTo(VeterinaryClinic::class, 'clinic_id');
        }

        /**
         * Relations: User linked to this veterinarian
         */
        public function user(): BelongsTo
        {
            return $this->belongsTo(\App\Models\User::class, 'user_id');
        }

        /**
         * Relations: Appointments
         */
        public function appointments(): HasMany
        {
            return $this->hasMany(VeterinaryAppointment::class, 'veterinarian_id');
        }
}

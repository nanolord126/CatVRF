<?php declare(strict_types=1);

namespace App\Domains\Pet\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class PetVet extends Model
{
    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use HasFactory;
        use SoftDeletes;

        protected $table = 'pet_vets';

        protected $fillable = [
            'tenant_id',
            'clinic_id',
            'user_id',
            'full_name',
            'specialization',
            'experience_years',
            'bio',
            'license_number',
            'certifications',
            'phone',
            'rating',
            'review_count',
            'appointment_count',
            'consultation_price',
            'availability',
            'is_active',
            'correlation_id',
            'uuid',
        ];

        protected $casts = [
            'certifications' => 'collection',
            'availability' => 'collection',
            'rating' => 'float',
            'consultation_price' => 'float',
            'review_count' => 'integer',
            'appointment_count' => 'integer',
            'experience_years' => 'integer',
            'is_active' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];

        protected $hidden = ['correlation_id'];

        public function booted(): void
        {
            static::addGlobalScope('tenant', function ($query) {
                if (auth()->check()) {
                    $query->where('tenant_id', tenant()->id);
                }
            });
        }

        public function clinic(): BelongsTo
        {
            return $this->belongsTo(PetClinic::class);
        }

        public function user(): BelongsTo
        {
            return $this->belongsTo(User::class);
        }

        public function appointments(): HasMany
        {
            return $this->hasMany(PetAppointment::class, 'vet_id');
        }

        public function medicalRecords(): HasMany
        {
            return $this->hasMany(PetMedicalRecord::class, 'vet_id');
        }

        public function reviews(): HasMany
        {
            return $this->hasMany(PetReview::class, 'vet_id');
        }
}

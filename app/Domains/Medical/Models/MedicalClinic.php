<?php declare(strict_types=1);

namespace App\Domains\Medical\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class MedicalClinic extends Model
{
    use HasFactory;

    use SoftDeletes;

        protected $table = 'medical_clinics';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'business_group_id',
            'owner_id',
            'name',
            'description',
            'address',
            'geo_point',
            'phone',
            'email',
            'specializations',
            'schedule',
            'logo_url',
            'rating',
            'review_count',
            'doctor_count',
            'is_verified',
            'is_active',
            'license_number',
            'amenities',
            'correlation_id',
            'tags',
        ];

        protected $hidden = ['deleted_at', 'correlation_id'];

        protected $casts = [
            'specializations' => 'array',
            'schedule' => 'array',
            'amenities' => 'array',
            'tags' => 'array',
            'rating' => 'float',
            'is_verified' => 'boolean',
            'is_active' => 'boolean',
        ];

        protected static function booted_disabled(): void
        {
            static::addGlobalScope('tenant', function ($query) {
                $query->where('tenant_id', tenant()->id ?? 0);
            });
        }

        public function owner(): BelongsTo
        {
            return $this->belongsTo(\App\Models\User::class, 'owner_id');
        }

        public function businessGroup(): BelongsTo
        {
            return $this->belongsTo(\App\Domains\Tenant\Models\BusinessGroup::class);
        }

        public function doctors(): HasMany
        {
            return $this->hasMany(MedicalDoctor::class, 'clinic_id');
        }

        public function services(): HasMany
        {
            return $this->hasMany(MedicalService::class, 'clinic_id');
        }

        public function appointments(): HasMany
        {
            return $this->hasMany(MedicalAppointment::class, 'clinic_id');
        }

        public function reviews(): HasMany
        {
            return $this->hasMany(MedicalReview::class, 'clinic_id');
        }
}

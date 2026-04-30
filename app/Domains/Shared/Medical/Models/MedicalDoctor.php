<?php

declare(strict_types=1);

namespace App\Domains\Shared\Medical\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

final class MedicalDoctor extends Model
{
    use TenantScoped;

    protected $table = 'medical_doctors';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'clinic_id',
        'user_id',
        'full_name',
        'specialization',
        'sub_specializations',
        'experience_years',
        'bio',
        'education',
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
        'tags',
    ];

    protected $hidden = ['deleted_at', 'correlation_id'];

    protected $casts = [
        'certifications' => 'array',
        'availability' => 'array',
        'sub_specializations' => 'array',
        'tags' => 'array',
        'rating' => 'float',
        'consultation_price' => 'float',
        'is_active' => 'boolean',
    ];

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(MedicalClinic::class, 'clinic_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(MedicalAppointment::class, 'doctor_id');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(MedicalReview::class, 'doctor_id');
    }

    public function records(): HasMany
    {
        return $this->hasMany(MedicalRecord::class, 'doctor_id');
    }

    public function prescriptions(): HasMany
    {
        return $this->hasMany(MedicalPrescription::class, 'doctor_id');
    }

    protected static function booted_disabled(): void
    {
        self::addGlobalScope('tenant', function ($query) {
            $query->where('tenant_id', tenant()->id ?? 0);
        });
    }
}

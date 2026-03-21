<?php declare(strict_types=1);

namespace App\Domains\Medical\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class MedicalDoctor extends Model
{
    use SoftDeletes;

    protected $table = 'medical_doctors';

    protected $fillable = [
        'tenant_id',
        'clinic_id',
        'user_id',
        'full_name',
        'specialization',
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
    ];

    protected $hidden = ['deleted_at'];

    protected $casts = [
        'certifications' => 'collection',
        'availability' => 'collection',
        'rating' => 'float',
        'consultation_price' => 'float',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function ($query) {
            if ($tenantId = auth()?->user()?->tenant_id ?? filament()?->getTenant()?->id) {
                $query->where('tenant_id', $tenantId);
            }
        });
    }

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(MedicalClinic::class, 'clinic_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
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
}

<?php declare(strict_types=1);

namespace App\Domains\Medical\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class MedicalClinic extends Model
{
    use SoftDeletes;

    protected $table = 'medical_clinics';

    protected $fillable = [
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
        'correlation_id',
    ];

    protected $hidden = ['deleted_at'];

    protected $casts = [
        'specializations' => 'collection',
        'schedule' => 'collection',
        'rating' => 'float',
        'is_verified' => 'boolean',
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

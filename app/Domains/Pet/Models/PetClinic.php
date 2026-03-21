<?php declare(strict_types=1);

namespace App\Domains\Pet\Models;

use App\Models\User;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class PetClinic extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'pet_clinics';

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
        'services',
        'schedule',
        'logo_url',
        'rating',
        'review_count',
        'vet_count',
        'is_verified',
        'is_active',
        'license_number',
        'correlation_id',
        'uuid',
    ];

    protected $casts = [
        'services' => 'collection',
        'schedule' => 'collection',
        'rating' => 'float',
        'review_count' => 'integer',
        'vet_count' => 'integer',
        'is_verified' => 'boolean',
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

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function vets(): HasMany
    {
        return $this->hasMany(PetVet::class, 'clinic_id');
    }

    public function services(): HasMany
    {
        return $this->hasMany(PetGroomingService::class, 'clinic_id');
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(PetAppointment::class, 'clinic_id');
    }

    public function products(): HasMany
    {
        return $this->hasMany(PetProduct::class, 'clinic_id');
    }

    public function boardingReservations(): HasMany
    {
        return $this->hasMany(PetBoardingReservation::class, 'clinic_id');
    }

    public function medicalRecords(): HasMany
    {
        return $this->hasMany(PetMedicalRecord::class, 'clinic_id');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(PetReview::class, 'clinic_id');
    }
}

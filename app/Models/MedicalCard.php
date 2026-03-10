<?php

namespace App\Models;

use App\Traits\StrictTenantIsolation;
use App\Traits\HasEcosystemTracing;

use Illuminate\Database\Eloquent\Model;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class MedicalCard extends Model
{
    use StrictTenantIsolation;
    use HasEcosystemTracing;
    protected $fillable = [
        'patient_type', // HUMAN, ANIMAL
        'patient_id',   // users.id (for Human) or animals.id (for Animal)
        'doctor_id',    // users.id
        'appointment_id',
        'symptoms',
        'diagnosis',
        'prescription',
        'status',
        'correlation_id',
    ];

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    public function patient()
    {
        if ($this->patient_type === 'HUMAN') {
            return $this->belongsTo(User::class, 'patient_id');
        }
        return $this->belongsTo(Animal::class, 'patient_id');
    }

    public function consumables(): HasMany
    {
        return $this->hasMany(MedicalConsumable::class);
    }
}










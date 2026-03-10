<?php

namespace App\Models\Tenants;

use App\Traits\StrictTenantIsolation;
use App\Traits\HasEcosystemTracing;

use Illuminate\Database\Eloquent\Model;
use Stancl\\Tenancy\\Database\\Concerns\\BelongsToTenant;
use App\Models\User;

class MedicalAppointment extends Model

{
    use BelongsToTenant;
    use StrictTenantIsolation;
    use HasEcosystemTracing;
    protected $fillable = ['entity_type', 'doctor_id', 'patient_name', 'scheduled_at', 'notes', 'status'];
    protected $casts = ['scheduled_at' => 'datetime'];

    public function doctor()
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }
}









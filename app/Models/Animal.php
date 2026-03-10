<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\\HasEcosystemTracing;

use App\Traits\StrictTenantIsolation;
use Illuminate\Database\Eloquent\Model;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Animal extends Model
{
    use StrictTenantIsolation;
    use HasEcosystemTracing;
    use StrictTenantIsolation;

    protected $fillable = [
        'owner_id', // users.id
        'name',
        'species',
        'breed',
        'birth_date',
        'gender',
        'weight',
        'chip_number',
        'notes',
        'correlation_id',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function medicalCards(): HasMany
    {
        return $this->hasMany(MedicalCard::class, 'animal_id');
    }

    public function vaccinations(): HasMany
    {
        return $this->hasMany(Vaccination::class);
    }
}









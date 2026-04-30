<?php

declare(strict_types=1);

namespace Modules\Veterinary\Infrastructure\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Model;
use Modules\Veterinary\Domain\Entities\Pet;
use Carbon\CarbonImmutable;

class PetModel extends Model
{
    use TenantScoped;

    protected $table = 'pets';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'owner_id',
        'name',
        'species',
        'breed',
        'birth_date',
        'gender',
        'weight',
        'medical_notes',
        'vaccination_history',
        'chip_number',
        'chip_installed_at',
        'passport_number',
        'is_neutered',
        'tags',
        'correlation_id',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'chip_installed_at' => 'date',
        'is_neutered' => 'boolean',
        'weight' => 'float',
        'vaccination_history' => 'json',
        'tags' => 'json',
        'medical_notes' => \App\Casts\AES256EncryptedCast::class,
    ];

    public function toDomain(): Pet
    {
        return new Pet(
            id: $this->id,
            uuid: $this->uuid,
            tenantId: $this->tenant_id,
            ownerId: $this->owner_id,
            name: $this->name,
            species: $this->species,
            breed: $this->breed,
            birthDate: $this->birth_date ? CarbonImmutable::parse($this->birth_date) : null,
            gender: $this->gender,
            weight: $this->weight,
            medicalNotes: $this->medical_notes,
            vaccinationHistory: $this->vaccination_history,
            chipNumber: $this->chip_number,
            chipInstalledAt: $this->chip_installed_at ? CarbonImmutable::parse($this->chip_installed_at) : null,
            passportNumber: $this->passport_number,
            isNeutered: $this->is_neutered,
            tags: $this->tags,
            correlationId: $this->correlation_id,
            createdAt: CarbonImmutable::parse($this->created_at),
            updatedAt: CarbonImmutable::parse($this->updated_at),
        );
    }
}

<?php

declare(strict_types=1);

namespace Modules\Fitness\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Fitness\Domain\Entities\Client as ClientEntity;

final class ClientModel extends Model
{
    use SoftDeletes;

    protected $table = 'fitness_clients';

    protected $fillable = [
        'tenant_id',
        'user_id',
        'first_name',
        'last_name',
        'patronymic',
        'phone',
        'email',
        'birth_date',
        'gender',
        'medical_restrictions',
        'emergency_contact',
        'emergency_phone',
        'goals',
        'fitness_level',
        'loyalty_points',
        'total_visits',
        'photo_url',
        'notes',
    ];

    protected $casts = [
        'birth_date' => 'datetime',
        'medical_restrictions' => 'array',
        'goals' => 'array',
        'loyalty_points' => 'float',
        'total_visits' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(MembershipModel::class, 'client_id');
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(BookingModel::class, 'client_id');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(AttendanceModel::class, 'client_id');
    }

    public function toDomain(): ClientEntity
    {
        return new ClientEntity(
            id: $this->id,
            tenantId: $this->tenant_id,
            userId: $this->user_id,
            firstName: $this->first_name,
            lastName: $this->last_name,
            patronymic: $this->patronymic,
            phone: $this->phone,
            email: $this->email,
            birthDate: $this->birth_date ? \Carbon\CarbonImmutable::parse($this->birth_date) : null,
            gender: $this->gender,
            medicalRestrictions: $this->medical_restrictions,
            emergencyContact: $this->emergency_contact,
            emergencyPhone: $this->emergency_phone,
            goals: $this->goals,
            fitnessLevel: $this->fitness_level,
            loyaltyPoints: $this->loyalty_points,
            totalVisits: $this->total_visits,
            photoUrl: $this->photo_url,
            notes: $this->notes,
            createdAt: \Carbon\CarbonImmutable::parse($this->created_at),
            updatedAt: \Carbon\CarbonImmutable::parse($this->updated_at),
        );
    }

    public static function fromDomain(ClientEntity $entity): self
    {
        return new self([
            'id' => $entity->id > 0 ? $entity->id : null,
            'tenant_id' => $entity->tenantId,
            'user_id' => $entity->userId,
            'first_name' => $entity->firstName,
            'last_name' => $entity->lastName,
            'patronymic' => $entity->patronymic,
            'phone' => $entity->phone,
            'email' => $entity->email,
            'birth_date' => $entity->birthDate,
            'gender' => $entity->gender,
            'medical_restrictions' => $entity->medicalRestrictions,
            'emergency_contact' => $entity->emergencyContact,
            'emergency_phone' => $entity->emergencyPhone,
            'goals' => $entity->goals,
            'fitness_level' => $entity->fitnessLevel,
            'loyalty_points' => $entity->loyaltyPoints,
            'total_visits' => $entity->totalVisits,
            'photo_url' => $entity->photoUrl,
            'notes' => $entity->notes,
        ]);
    }
}

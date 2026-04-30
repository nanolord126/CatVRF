<?php

declare(strict_types=1);

namespace Modules\Fitness\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Fitness\Domain\Entities\Membership as MembershipEntity;
use Modules\Fitness\Domain\Enums\MembershipStatus;
use Modules\Fitness\Domain\Enums\MembershipType;

final class MembershipModel extends Model
{
    use SoftDeletes;

    protected $table = 'fitness_memberships';

    protected $fillable = [
        'tenant_id',
        'client_id',
        'type',
        'status',
        'start_date',
        'end_date',
        'remaining_visits',
        'total_visits',
        'price',
        'allow_freeze',
        'freeze_days_used',
        'max_freeze_days',
        'frozen_at',
        'unfrozen_at',
        'notes',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'frozen_at' => 'datetime',
        'unfrozen_at' => 'datetime',
        'remaining_visits' => 'integer',
        'total_visits' => 'integer',
        'price' => 'float',
        'allow_freeze' => 'boolean',
        'freeze_days_used' => 'integer',
        'max_freeze_days' => 'integer',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(ClientModel::class, 'client_id');
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(BookingModel::class, 'membership_id');
    }

    public function toDomain(): MembershipEntity
    {
        return new MembershipEntity(
            id: $this->id,
            tenantId: $this->tenant_id,
            clientId: $this->client_id,
            type: MembershipType::from($this->type),
            status: MembershipStatus::from($this->status),
            startDate: \Carbon\CarbonImmutable::parse($this->start_date),
            endDate: \Carbon\CarbonImmutable::parse($this->end_date),
            remainingVisits: $this->remaining_visits,
            totalVisits: $this->total_visits,
            price: $this->price,
            allowFreeze: $this->allow_freeze,
            freezeDaysUsed: $this->freeze_days_used,
            maxFreezeDays: $this->max_freeze_days,
            frozenAt: $this->frozen_at ? \Carbon\CarbonImmutable::parse($this->frozen_at) : null,
            unfrozenAt: $this->unfrozen_at ? \Carbon\CarbonImmutable::parse($this->unfrozen_at) : null,
            notes: $this->notes,
            createdAt: \Carbon\CarbonImmutable::parse($this->created_at),
            updatedAt: \Carbon\CarbonImmutable::parse($this->updated_at),
        );
    }

    public static function fromDomain(MembershipEntity $entity): self
    {
        return new self([
            'id' => $entity->id > 0 ? $entity->id : null,
            'tenant_id' => $entity->tenantId,
            'client_id' => $entity->clientId,
            'type' => $entity->type->value,
            'status' => $entity->status->value,
            'start_date' => $entity->startDate,
            'end_date' => $entity->endDate,
            'remaining_visits' => $entity->remainingVisits,
            'total_visits' => $entity->totalVisits,
            'price' => $entity->price,
            'allow_freeze' => $entity->allowFreeze,
            'freeze_days_used' => $entity->freezeDaysUsed,
            'max_freeze_days' => $entity->maxFreezeDays,
            'frozen_at' => $entity->frozenAt,
            'unfrozen_at' => $entity->unfrozenAt,
            'notes' => $entity->notes,
        ]);
    }
}

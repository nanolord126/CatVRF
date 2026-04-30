<?php

declare(strict_types=1);

namespace Modules\Hotels\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Hotels\Domain\Entities\LoyaltyProgram;
use Modules\Hotels\Domain\Enums\GuestLoyaltyLevel;

class LoyaltyProgramModel extends Model
{
    use SoftDeletes;

    protected $table = 'hotels_loyalty_programs';

    protected $fillable = [
        'tenant_id',
        'venue_id',
        'uuid',
        'name',
        'description',
        'level',
        'points_per_night',
        'points_to_rubles_rate',
        'benefits',
        'is_active',
        'valid_from',
        'valid_until',
    ];

    protected $casts = [
        'points_per_night' => 'integer',
        'points_to_rubles_rate' => 'float',
        'benefits' => 'array',
        'is_active' => 'boolean',
        'valid_from' => 'datetime',
        'valid_until' => 'datetime',
    ];

    public function venue(): BelongsTo
    {
        return $this->belongsTo(VenueModel::class, 'venue_id');
    }

    public function toDomain(): LoyaltyProgram
    {
        return new LoyaltyProgram(
            id: $this->id,
            tenantId: $this->tenant_id,
            venueId: $this->venue_id,
            uuid: $this->uuid,
            name: $this->name,
            description: $this->description,
            level: GuestLoyaltyLevel::from($this->level),
            pointsPerNight: $this->points_per_night,
            pointsToRublesRate: $this->points_to_rubles_rate,
            benefits: $this->benefits,
            isActive: $this->is_active,
            validFrom: $this->valid_from ? \Carbon\CarbonImmutable::parse($this->valid_from) : null,
            validUntil: $this->valid_until ? \Carbon\CarbonImmutable::parse($this->valid_until) : null,
            createdAt: \Carbon\CarbonImmutable::parse($this->created_at),
            updatedAt: \Carbon\CarbonImmutable::parse($this->updated_at),
        );
    }

    public static function fromDomain(LoyaltyProgram $program): self
    {
        return new self([
            'id' => $program->id,
            'tenant_id' => $program->tenantId,
            'venue_id' => $program->venueId,
            'uuid' => $program->uuid,
            'name' => $program->name,
            'description' => $program->description,
            'level' => $program->level->value,
            'points_per_night' => $program->pointsPerNight,
            'points_to_rubles_rate' => $program->pointsToRublesRate,
            'benefits' => $program->benefits,
            'is_active' => $program->isActive,
            'valid_from' => $program->validFrom?->toDateTimeString(),
            'valid_until' => $program->validUntil?->toDateTimeString(),
        ]);
    }
}

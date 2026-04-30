<?php

declare(strict_types=1);

namespace Modules\Hotels\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Hotels\Domain\Entities\Service;
use Modules\Hotels\Domain\Enums\ServiceType;

class ServiceModel extends Model
{
    use SoftDeletes;

    protected $table = 'hotels_services';

    protected $fillable = [
        'tenant_id',
        'venue_id',
        'uuid',
        'name',
        'description',
        'type',
        'base_price',
        'currency',
        'is_available',
        'is_optional',
        'icon',
        'pricing_rules',
        'availability_rules',
        'duration_minutes',
        'max_quantity_per_booking',
        'sort_order',
    ];

    protected $casts = [
        'base_price' => 'float',
        'is_available' => 'boolean',
        'is_optional' => 'boolean',
        'pricing_rules' => 'array',
        'availability_rules' => 'array',
        'duration_minutes' => 'integer',
        'max_quantity_per_booking' => 'integer',
        'sort_order' => 'integer',
    ];

    public function venue(): BelongsTo
    {
        return $this->belongsTo(VenueModel::class, 'venue_id');
    }

    public function toDomain(): Service
    {
        return new Service(
            id: $this->id,
            tenantId: $this->tenant_id,
            venueId: $this->venue_id,
            uuid: $this->uuid,
            name: $this->name,
            description: $this->description,
            type: ServiceType::from($this->type),
            basePrice: $this->base_price,
            currency: $this->currency,
            isAvailable: $this->is_available,
            isOptional: $this->is_optional,
            icon: $this->icon,
            pricingRules: $this->pricing_rules,
            availabilityRules: $this->availability_rules,
            durationMinutes: $this->duration_minutes,
            maxQuantityPerBooking: $this->max_quantity_per_booking,
            sortOrder: $this->sort_order,
            createdAt: \Carbon\CarbonImmutable::parse($this->created_at),
            updatedAt: \Carbon\CarbonImmutable::parse($this->updated_at),
        );
    }

    public static function fromDomain(Service $service): self
    {
        return new self([
            'id' => $service->id,
            'tenant_id' => $service->tenantId,
            'venue_id' => $service->venueId,
            'uuid' => $service->uuid,
            'name' => $service->name,
            'description' => $service->description,
            'type' => $service->type->value,
            'base_price' => $service->basePrice,
            'currency' => $service->currency,
            'is_available' => $service->isAvailable,
            'is_optional' => $service->isOptional,
            'icon' => $service->icon,
            'pricing_rules' => $service->pricingRules,
            'availability_rules' => $service->availabilityRules,
            'duration_minutes' => $service->durationMinutes,
            'max_quantity_per_booking' => $service->maxQuantityPerBooking,
            'sort_order' => $service->sortOrder,
        ]);
    }
}

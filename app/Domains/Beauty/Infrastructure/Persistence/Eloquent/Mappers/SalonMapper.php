<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Infrastructure\Persistence\Eloquent\Mappers;

use App\Domains\Beauty\Domain\Entities\Salon;
use App\Domains\Beauty\Domain\ValueObjects\SalonId;
use App\Domains\Beauty\Domain\ValueObjects\Schedule;
use App\Domains\Beauty\Infrastructure\Persistence\Eloquent\Models\BeautySalon as EloquentSalon;
use App\Shared\Domain\ValueObjects\Address;
use App\Shared\Domain\ValueObjects\Photo;
use App\Shared\Domain\ValueObjects\TenantId;
use Illuminate\Support\Collection;

/**
 * Class SalonMapper
 *
 * Part of the Beauty vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Component of the CatVRF platform.
 * Follows strict coding standards:
 * - final class (no inheritance unless required)
 * - private properties
 * - Constructor injection only
 * - correlation_id in all operations
 *
 * @package App\Domains\Beauty\Infrastructure\Persistence\Eloquent\Mappers
 */
final class SalonMapper
{
    public function toDomain(EloquentSalon $eloquentSalon): Salon
    {
        return new Salon(
            id: SalonId::fromString($eloquentSalon->uuid),
            tenantId: new TenantId($eloquentSalon->tenant_id),
            name: $eloquentSalon->name,
            address: new Address(
                fullAddress: $eloquentSalon->address_full,
                lat: $eloquentSalon->address_lat,
                lon: $eloquentSalon->address_lon
            ),
            schedule: new Schedule($eloquentSalon->schedule),
            previewPhoto: $eloquentSalon->preview_photo_path ? new Photo($eloquentSalon->preview_photo_path) : null,
            masters: new Collection(), // Lazy loaded
            services: new Collection(), // Lazy loaded
            rating: $eloquentSalon->rating,
            reviewCount: $eloquentSalon->review_count,
            createdAt: new \DateTimeImmutable($eloquentSalon->created_at),
            updatedAt: new \DateTimeImmutable($eloquentSalon->updated_at),
        );
    }

    public function toEloquent(Salon $salon): EloquentSalon
    {
        $eloquentSalon = EloquentSalon::firstOrNew(['uuid' => $salon->id->getValue()]);
        $eloquentSalon->uuid = $salon->id->getValue();
        $eloquentSalon->tenant_id = $salon->tenantId->getValue();
        $eloquentSalon->name = $salon->name;
        $eloquentSalon->address_full = $salon->address->fullAddress;
        $eloquentSalon->address_lat = $salon->address->lat;
        $eloquentSalon->address_lon = $salon->address->lon;
        $eloquentSalon->schedule = $salon->schedule->getWeeklySchedule();
        $eloquentSalon->preview_photo_path = $salon->previewPhoto?->getPath();
        $eloquentSalon->rating = $salon->rating;
        $eloquentSalon->review_count = $salon->reviewCount;

        return $eloquentSalon;
    }
}

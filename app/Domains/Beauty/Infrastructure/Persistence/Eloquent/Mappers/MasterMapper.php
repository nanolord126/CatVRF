<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Infrastructure\Persistence\Eloquent\Mappers;

use App\Domains\Beauty\Domain\Entities\Master;
use App\Domains\Beauty\Domain\ValueObjects\MasterId;
use App\Domains\Beauty\Domain\ValueObjects\SalonId;
use App\Domains\Beauty\Domain\ValueObjects\Schedule;
use App\Domains\Beauty\Infrastructure\Persistence\Eloquent\Models\BeautyMaster as EloquentMaster;
use App\Shared\Domain\ValueObjects\Photo;
use Illuminate\Support\Collection;

/**
 * Class MasterMapper
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
final class MasterMapper
{
    public function toDomain(EloquentMaster $eloquentMaster): Master
    {
        return new Master(
            id: MasterId::fromString($eloquentMaster->uuid),
            salonId: SalonId::fromString($eloquentMaster->salon->uuid),
            name: $eloquentMaster->name,
            specialization: $eloquentMaster->specialization,
            experienceYears: $eloquentMaster->experience_years,
            schedule: new Schedule($eloquentMaster->schedule),
            photo: $eloquentMaster->photo_path ? new Photo($eloquentMaster->photo_path) : null,
            services: new Collection(), // Lazy loaded
            portfolio: new Collection(), // Lazy loaded
            rating: $eloquentMaster->rating,
            reviewCount: $eloquentMaster->review_count,
            createdAt: new \DateTimeImmutable($eloquentMaster->created_at),
            updatedAt: new \DateTimeImmutable($eloquentMaster->updated_at),
        );
    }

    public function toEloquent(Master $master): EloquentMaster
    {
        $eloquentMaster = EloquentMaster::firstOrNew(['uuid' => $master->id->getValue()]);
        $eloquentMaster->uuid = $master->id->getValue();

        $eloquentSalon = \App\Domains\Beauty\Infrastructure\Persistence\Eloquent\Models\BeautySalon::where('uuid', $master->salonId->getValue())->firstOrFail();
        $eloquentMaster->salon_id = $eloquentSalon->id;

        $eloquentMaster->name = $master->name;
        $eloquentMaster->specialization = $master->specialization;
        $eloquentMaster->experience_years = $master->experienceYears;
        $eloquentMaster->schedule = $master->schedule->getWeeklySchedule();
        $eloquentMaster->photo_path = $master->photo?->getPath();
        $eloquentMaster->rating = $master->rating;
        $eloquentMaster->review_count = $master->reviewCount;

        return $eloquentMaster;
    }
}

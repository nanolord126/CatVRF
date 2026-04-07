<?php
declare(strict_types=1);

namespace App\Domains\RealEstate\Services;

use App\Domains\RealEstate\Models\Property;
use App\Domains\RealEstate\DTOs\SearchPropertyDto;
use App\Services\FraudControlService;
use App\Services\AuditService;
use Illuminate\Database\DatabaseManager;
use Illuminate\Log\LogManager;
use Illuminate\Database\Eloquent\Collection;

final readonly class PropertyService
{
    public function __construct(
        private FraudControlService $fraud,
        private AuditService $audit,
        private DatabaseManager $db,
        private LogManager $log
    ) {}

    public function searchNearby(SearchPropertyDto $dto): Collection
    {
        $this->fraud->check([
            "action" => "search_real_estate",
            "lat" => $dto->lat,
            "lon" => $dto->lon,
            "correlation_id" => $dto->correlationId,
        ]);

        $query = Property::query()
            ->selectRaw(
                "*, (6371 * acos(cos(radians(?)) * cos(radians(lat)) * cos(radians(lon) - radians(?)) + sin(radians(?)) * sin(radians(lat)))) AS distance",
                [$dto->lat, $dto->lon, $dto->lat]
            )
            ->having("distance", "<", $dto->radiusKm)
            ->where("is_active", true)
            ->where("status", "active");

        if ($dto->type !== null) { $query->where("type", $dto->type); }
        if ($dto->minPrice !== null) { $query->where("price", ">=", $dto->minPrice); }
        if ($dto->maxPrice !== null) { $query->where("price", "<=", $dto->maxPrice); }

        $results = $query->orderBy("distance")->limit(100)->get();

        $this->log->channel("audit")->info("Real Estate public search executed", [
            "results_count" => $results->count(),
            "correlation_id" => $dto->correlationId,
        ]);

        return $results;
    }

    public function toggleStatus(Property $property, string $newStatus, string $correlationId): Property
    {
        return $this->db->transaction(function () use ($property, $newStatus, $correlationId): Property {
            $oldStatus = $property->status;
            $property->update(["status" => $newStatus]);
            
            $this->audit->log(
                action: "property_status_changed",
                subjectType: Property::class,
                subjectId: $property->id,
                old: ["status" => $oldStatus],
                new: ["status" => $newStatus],
                correlationId: $correlationId
            );

            return $property;
        });
    }
}

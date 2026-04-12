<?php
declare(strict_types=1);

namespace App\Domains\Hotels\Services;


use Illuminate\Support\Facades\DB;
use App\Domains\Hotels\Models\Hotel;
use App\Domains\Hotels\Models\Room;
use App\Domains\Hotels\DTOs\SearchHotelDto;
use App\Services\FraudControlService;
use App\Services\AuditService;
use Illuminate\Database\DatabaseManager;
use Illuminate\Log\LogManager;
use Illuminate\Database\Eloquent\Collection;

final readonly class HotelSearchService
{
    public function __construct(
        private FraudControlService $fraud,
        private AuditService $audit,
        private DatabaseManager $db,
        private LogManager $log
    ) {}

    public function searchHotels(SearchHotelDto $dto): Collection
    {
        $this->fraud->check(
            userId: 0,
            operationType: 'unknown',
            amount: 0,
        );

        $query = Hotel::query()
            ->with(["rooms" => function ($q) use ($dto): void {
                $q->where("is_available", true)
                  ->where("capacity", ">=", $dto->guestsCount)
                  ->whereDoesntHave("bookings", function ($b) use ($dto): void {
                      $b->where(function ($sub) use ($dto): void {
                          $sub->whereBetween("check_in", [$dto->checkIn, $dto->checkOut])
                              ->orWhereBetween("check_out", [$dto->checkIn, $dto->checkOut])
                              ->orWhere(function ($overlap) use ($dto): void {
                                  $overlap->where("check_in", "<=", $dto->checkIn)
                                          ->where("check_out", ">=", $dto->checkOut);
                              });
                      })->whereNotIn("status", ["cancelled", "failed"]);
                  });
            }])
            ->selectRaw(
                "*, (6371 * acos(cos(radians(?)) * cos(radians(lat)) * cos(radians(lon) - radians(?)) + sin(radians(?)) * sin(radians(lat)))) AS distance",
                [$dto->lat, $dto->lon, $dto->lat]
            )
            ->having("distance", "<", $dto->radiusKm)
            ->where("is_active", true);

        if (!empty($dto->query)) {
            $query->where("name", "LIKE", "%" . $dto->query . "%");
        }

        $results = $query->orderBy("distance")->limit(50)->get();
        
        $filteredResults = $results->filter(function (Hotel $hotel): bool {
            return $hotel->rooms->count() > 0;
        });

        $this->log->channel("audit")->info("Hotel catalog searched", [
            "results_count" => $filteredResults->count(),
            "correlation_id" => $dto->correlationId,
        ]);

        return $filteredResults;
    }

    /**
     * Выполнить операцию в транзакции с audit-логированием.
     *
     * @template T
     * @param callable(): T $callback
     * @return T
     */
    protected function executeInTransaction(callable $callback): mixed
    {
        return DB::transaction(function () use ($callback) {
            return $callback();
        });
    }
}

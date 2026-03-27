<?php

declare(strict_types=1);

namespace App\Domains\EventPlanning\Entertainment\Services;

use App\Domains\EventPlanning\Entertainment\Models\Venue;
use App\Domains\EventPlanning\Entertainment\Models\SeatMap;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * КАНОН 2026 — SEAT MAP SERVICE
 * 1. final readonly class
 * 2. Layout management
 * 3. Atomic updates
 */
final readonly class SeatMapService
{
    public function __construct(
        private string $correlationId = ''
    ) {
    }

    private function getCorrelationId(): string
    {
        return $this->correlationId ?: (string) Str::uuid();
    }

    /**
     * Создать или обновить схему зала
     */
    public function saveSeatMap(Venue $venue, string $name, array $layout, array $categories): SeatMap
    {
        $correlationId = $this->getCorrelationId();

        Log::channel('audit')->info('Saving seat map', [
            'venue_uuid' => $venue->uuid,
            'name' => $name,
            'correlation_id' => $correlationId,
        ]);

        return DB::transaction(function () use ($venue, $name, $layout, $categories, $correlationId) {
            /** @var SeatMap $seatMap */
            $seatMap = SeatMap::updateOrCreate(
                [
                    'venue_id' => $venue->id,
                    'name' => $name,
                    'tenant_id' => $venue->tenant_id,
                ],
                [
                    'layout' => $layout,
                    'categories' => $categories,
                    'correlation_id' => $correlationId,
                ]
            );

            Log::channel('audit')->info('Seat map saved successfully', [
                'seat_map_uuid' => $seatMap->uuid,
                'correlation_id' => $correlationId,
            ]);

            return $seatMap;
        });
    }

    /**
     * Получить все активные схемы для заведения
     */
    public function getVenueSeatMaps(Venue $venue): Collection
    {
        return $venue->seatMaps()->orderBy('name')->get();
    }

    /**
     * Удалить схему зала
     */
    public function deleteSeatMap(SeatMap $seatMap): bool
    {
        $correlationId = $this->getCorrelationId();

        Log::channel('audit')->warning('Deleting seat map', [
            'seat_map_uuid' => $seatMap->uuid,
            'correlation_id' => $correlationId,
        ]);

        return DB::transaction(function () use ($seatMap, $correlationId) {
            $lockingMap = SeatMap::where('id', $seatMap->id)->lockForUpdate()->first();
            
            // Здесь можно добавить проверку: если есть активные события на этой схеме — запретить удаление
            
            $lockingMap->delete();

            Log::channel('audit')->info('Seat map deleted successfully', [
                'correlation_id' => $correlationId,
            ]);

            return true;
        });
    }
}

<?php declare(strict_types=1);

namespace App\Domains\Auto\Services\AI;

use Illuminate\Database\DatabaseManager;

/**
 * AutoServiceFinderService - Finds nearest auto services
 * 
 * Locates auto service centers based on geographic location.
 */
final readonly class AutoServiceFinderService
{
    public function __construct(
        private readonly DatabaseManager $db,
    ) {}

    /**
     * Find nearest services
     */
    public function findNearest(?float $latitude, ?float $longitude, int $tenantId): array
    {
        if ($latitude === null || $longitude === null) {
            return [];
        }

        $services = $this->db->table('auto_services')
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->select('*')
            ->selectRaw(
                '(6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude)))) AS distance',
                [$latitude, $longitude, $latitude]
            )
            ->having('distance', '<', 50)
            ->orderBy('distance')
            ->limit(5)
            ->get();

        return $services->map(function ($service) {
            return [
                'id' => $service->id,
                'name' => $service->name,
                'address' => $service->address,
                'distance_km' => round($service->distance, 2),
                'rating' => $service->rating ?? 0.0,
                'phone' => $service->phone ?? '',
                'instant_booking_available' => ($service->instant_booking_enabled ?? false) === true,
            ];
        })->toArray();
    }
}

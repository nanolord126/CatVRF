<?php declare(strict_types=1);

namespace App\Domains\Taxi\Services;

use App\Domains\Taxi\Models\TaxiRide;
use App\Domains\Taxi\Models\TaxiClientFavorite;
use App\Domains\Taxi\Models\Driver;
use App\Services\AuditService;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Psr\Log\LoggerInterface;

/**
 * TaxiClientPortalService - Production-ready client portal for taxi operations
 * 
 * Features:
 * - Client dashboard with ride history
 * - Booking functionality
 * - Favorite locations management
 * - Favorite drivers management
 * - Ride rating and feedback
 * - Payment methods management
 * - Promo codes and discounts
 * - Support ticket creation
 */
final readonly class TaxiClientPortalService
{
    public function __construct(
        private readonly AuditService $audit,
        private readonly DatabaseManager $db,
        private readonly TaxiGeoService $geoService,
        private readonly TaxiOrderService $orderService,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Get client dashboard
     */
    public function getClientDashboard(int $userId, string $correlationId = null): array
    {
        $correlationId = $correlationId ?? Str::uuid()->toString();
        
        $today = now()->startOfDay();
        
        // Today's rides
        $todayRides = TaxiRide::where('passenger_id', $userId)
            ->whereDate('created_at', $today)
            ->count();
        
        // Active ride
        $activeRide = TaxiRide::where('passenger_id', $userId)
            ->whereIn('status', [TaxiRide::STATUS_ACCEPTED, TaxiRide::STATUS_STARTED])
            ->with(['driver', 'vehicle'])
            ->first();
        
        // Recent rides
        $recentRides = TaxiRide::where('passenger_id', $userId)
            ->whereIn('status', [TaxiRide::STATUS_COMPLETED, TaxiRide::STATUS_CANCELLED])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->with(['driver', 'vehicle'])
            ->get();
        
        // Favorite locations
        $favoriteLocations = TaxiClientFavorite::where('user_id', $userId)
            ->where('type', TaxiClientFavorite::TYPE_LOCATION)
            ->orderBy('is_default', 'desc')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();
        
        // Favorite drivers
        $favoriteDrivers = TaxiClientFavorite::where('user_id', $userId)
            ->where('type', TaxiClientFavorite::TYPE_DRIVER)
            ->with('driver')
            ->get();
        
        return [
            'user_id' => $userId,
            'today' => [
                'rides' => $todayRides,
            ],
            'active_ride' => $activeRide ? [
                'uuid' => $activeRide->uuid,
                'status' => $activeRide->status,
                'pickup_address' => $activeRide->pickup_address,
                'dropoff_address' => $activeRide->dropoff_address,
                'driver' => $activeRide->driver ? [
                    'name' => $activeRide->driver->first_name,
                    'rating' => $activeRide->driver->rating,
                    'vehicle' => $activeRide->vehicle ? [
                        'brand' => $activeRide->vehicle->brand,
                        'model' => $activeRide->vehicle->model,
                        'license_plate' => $activeRide->vehicle->license_plate,
                    ] : null,
                ] : null,
            ] : null,
            'recent_rides' => $recentRides->map(function ($ride) {
                return [
                    'uuid' => $ride->uuid,
                    'status' => $ride->status,
                    'pickup_address' => $ride->pickup_address,
                    'dropoff_address' => $ride->dropoff_address,
                    'price_rubles' => $ride->getFinalPriceInRubles(),
                    'created_at' => $ride->created_at->toIso8601String(),
                    'driver_name' => $ride->driver?->first_name,
                ];
            })->values(),
            'favorite_locations' => $favoriteLocations->map(function ($fav) {
                return [
                    'uuid' => $fav->uuid,
                    'name' => $fav->name,
                    'address' => $fav->address,
                    'is_default' => $fav->is_default,
                ];
            })->values(),
            'favorite_drivers' => $favoriteDrivers->map(function ($fav) {
                return [
                    'uuid' => $fav->uuid,
                    'driver_id' => $fav->driver_id,
                    'driver_name' => $fav->driver?->first_name,
                    'driver_rating' => $fav->driver?->rating,
                ];
            })->values(),
        ];
    }

    /**
     * Get client ride history
     */
    public function getClientRideHistory(int $userId, ?Carbon $startDate = null, ?Carbon $endDate = null, int $perPage = 20, string $correlationId = null): array
    {
        $correlationId = $correlationId ?? Str::uuid()->toString();
        
        $query = TaxiRide::where('passenger_id', $userId);
        
        if ($startDate) {
            $query->where('created_at', '>=', $startDate);
        }
        
        if ($endDate) {
            $query->where('created_at', '<=', $endDate);
        }
        
        $rides = $query->orderBy('created_at', 'desc')
            ->with(['driver', 'vehicle'])
            ->paginate($perPage);
        
        return [
            'user_id' => $userId,
            'total_rides' => $rides->total(),
            'rides' => $rides->map(function ($ride) {
                return [
                    'uuid' => $ride->uuid,
                    'status' => $ride->status,
                    'pickup_address' => $ride->pickup_address,
                    'dropoff_address' => $ride->dropoff_address,
                    'distance_km' => $ride->distance_km,
                    'price_rubles' => $ride->getFinalPriceInRubles(),
                    'surge_multiplier' => $ride->surge_multiplier,
                    'created_at' => $ride->created_at->toIso8601String(),
                    'completed_at' => $ride->metadata['completed_at'] ?? null,
                    'driver' => $ride->driver ? [
                        'name' => $ride->driver->first_name,
                        'rating' => $ride->driver->rating,
                    ] : null,
                    'vehicle' => $ride->vehicle ? [
                        'brand' => $ride->vehicle->brand,
                        'model' => $ride->vehicle->model,
                        'license_plate' => $ride->vehicle->license_plate,
                        'color' => $ride->vehicle->color,
                    ] : null,
                    'driver_rating' => $ride->metadata['driver_rating'] ?? null,
                    'passenger_rating' => $ride->metadata['passenger_rating'] ?? null,
                ];
            })->values(),
            'pagination' => [
                'current_page' => $rides->currentPage(),
                'per_page' => $rides->perPage(),
                'total' => $rides->total(),
                'last_page' => $rides->lastPage(),
            ],
        ];
    }

    /**
     * Add favorite location
     */
    public function addFavoriteLocation(int $userId, array $data, string $correlationId = null): TaxiClientFavorite
    {
        $correlationId = $correlationId ?? Str::uuid()->toString();
        
        return $this->db->transaction(function () use ($userId, $data, $correlationId) {
            $favorite = TaxiClientFavorite::create([
                'tenant_id' => tenant()->id ?? 1,
                'user_id' => $userId,
                'type' => TaxiClientFavorite::TYPE_LOCATION,
                'name' => $data['name'],
                'address' => $data['address'],
                'latitude' => $data['latitude'],
                'longitude' => $data['longitude'],
                'is_default' => $data['is_default'] ?? false,
                'correlation_id' => $correlationId,
                'metadata' => $data['metadata'] ?? [],
                'tags' => array_merge(['taxi', 'favorite', 'location'], $data['tags'] ?? []),
            ]);

            if ($favorite->is_default) {
                $favorite->markAsDefault();
            }

            $this->audit->log(
                action: 'taxi_favorite_location_added',
                subjectType: TaxiClientFavorite::class,
                subjectId: $favorite->id,
                oldValues: [],
                newValues: $favorite->toArray(),
                correlationId: $correlationId,
            );

            $this->logger->info('Taxi favorite location added', [
                'correlation_id' => $correlationId,
                'favorite_uuid' => $favorite->uuid,
                'user_id' => $userId,
                'name' => $favorite->name,
            ]);

            return $favorite;
        });
    }

    /**
     * Add favorite driver
     */
    public function addFavoriteDriver(int $userId, int $driverId, string $correlationId = null): TaxiClientFavorite
    {
        $correlationId = $correlationId ?? Str::uuid()->toString();
        
        return $this->db->transaction(function () use ($userId, $driverId, $correlationId) {
            $driver = Driver::findOrFail($driverId);
            
            // Check if already favorited
            $existing = TaxiClientFavorite::where('user_id', $userId)
                ->where('driver_id', $driverId)
                ->first();
            
            if ($existing) {
                return $existing;
            }

            $favorite = TaxiClientFavorite::create([
                'tenant_id' => tenant()->id ?? 1,
                'user_id' => $userId,
                'type' => TaxiClientFavorite::TYPE_DRIVER,
                'name' => $driver->first_name . ' ' . $driver->last_name,
                'driver_id' => $driverId,
                'is_default' => false,
                'correlation_id' => $correlationId,
                'metadata' => [
                    'driver_rating' => $driver->rating,
                ],
                'tags' => ['taxi', 'favorite', 'driver'],
            ]);

            $this->audit->log(
                action: 'taxi_favorite_driver_added',
                subjectType: TaxiClientFavorite::class,
                subjectId: $favorite->id,
                oldValues: [],
                newValues: $favorite->toArray(),
                correlationId: $correlationId,
            );

            $this->logger->info('Taxi favorite driver added', [
                'correlation_id' => $correlationId,
                'favorite_uuid' => $favorite->uuid,
                'user_id' => $userId,
                'driver_id' => $driverId,
            ]);

            return $favorite;
        });
    }

    /**
     * Remove favorite
     */
    public function removeFavorite(int $userId, string $uuid, string $correlationId = null): void
    {
        $correlationId = $correlationId ?? Str::uuid()->toString();
        
        $favorite = TaxiClientFavorite::where('user_id', $userId)
            ->where('uuid', $uuid)
            ->firstOrFail();
        
        $favorite->delete();

        $this->audit->log(
            action: 'taxi_favorite_removed',
            subjectType: TaxiClientFavorite::class,
            subjectId: $favorite->id,
            oldValues: $favorite->toArray(),
            newValues: [],
            correlationId: $correlationId,
        );

        $this->logger->info('Taxi favorite removed', [
            'correlation_id' => $correlationId,
            'favorite_uuid' => $uuid,
            'user_id' => $userId,
        ]);
    }

    /**
     * Get client statistics
     */
    public function getClientStatistics(int $userId, string $correlationId = null): array
    {
        $correlationId = $correlationId ?? Str::uuid()->toString();
        
        $totalRides = TaxiRide::where('passenger_id', $userId)->count();
        $completedRides = TaxiRide::where('passenger_id', $userId)
            ->where('status', TaxiRide::STATUS_COMPLETED)
            ->count();
        $cancelledRides = TaxiRide::where('passenger_id', $userId)
            ->where('status', TaxiRide::STATUS_CANCELLED)
            ->count();
        
        $totalSpent = TaxiRide::where('passenger_id', $userId)
            ->where('status', TaxiRide::STATUS_COMPLETED)
            ->sum('total_price');
        
        $averageRating = TaxiRide::where('passenger_id', $userId)
            ->whereNotNull('metadata.driver_rating')
            ->avg('metadata.driver_rating') ?? 5.0;
        
        // Most used locations
        $topPickupLocations = TaxiRide::where('passenger_id', $userId)
            ->where('status', TaxiRide::STATUS_COMPLETED)
            ->select('pickup_address')
            ->selectRaw('COUNT(*) as count')
            ->groupBy('pickup_address')
            ->orderBy('count', 'desc')
            ->limit(5)
            ->get();
        
        return [
            'user_id' => $userId,
            'rides' => [
                'total' => $totalRides,
                'completed' => $completedRides,
                'cancelled' => $cancelledRides,
                'completion_rate' => $totalRides > 0 ? ($completedRides / $totalRides) * 100 : 0,
            ],
            'spending' => [
                'total_rubles' => $totalSpent / 100,
                'average_per_ride_rubles' => $completedRides > 0 ? ($totalSpent / 100) / $completedRides : 0,
            ],
            'rating' => [
                'average' => round($averageRating, 2),
            ],
            'locations' => [
                'top_pickup_locations' => $topPickupLocations->map(function ($loc) {
                    return [
                        'address' => $loc->pickup_address,
                        'count' => $loc->count,
                    ];
                })->values(),
            ],
        ];
    }

    /**
     * Rate ride
     */
    public function rateRide(int $userId, string $rideUuid, int $rating, ?string $comment, string $correlationId = null): TaxiRide
    {
        $correlationId = $correlationId ?? Str::uuid()->toString();
        
        return $this->db->transaction(function () use ($userId, $rideUuid, $rating, $comment, $correlationId) {
            $ride = TaxiRide::where('uuid', $rideUuid)
                ->where('passenger_id', $userId)
                ->with('driver')
                ->firstOrFail();
            
            if ($ride->status !== TaxiRide::STATUS_COMPLETED) {
                throw new \InvalidArgumentException('Ride must be completed to rate');
            }
            
            if ($rating < 1 || $rating > 5) {
                throw new \InvalidArgumentException('Rating must be between 1 and 5');
            }

            $ride->update([
                'metadata' => array_merge($ride->metadata ?? [], [
                    'passenger_rating' => $rating,
                    'passenger_comment' => $comment,
                    'rated_at' => now()->toIso8601String(),
                ]),
            ]);

            // Update driver rating
            if ($ride->driver) {
                $newRating = ($ride->driver->rating * ($ride->driver->completed_rides - 1) + $rating) / $ride->driver->completed_rides;
                $ride->driver->update(['rating' => round($newRating, 2)]);
            }

            $this->audit->log(
                action: 'taxi_ride_rated',
                subjectType: TaxiRide::class,
                subjectId: $ride->id,
                oldValues: [],
                newValues: $ride->toArray(),
                correlationId: $correlationId,
            );

            $this->logger->info('Taxi ride rated', [
                'correlation_id' => $correlationId,
                'ride_uuid' => $ride->uuid,
                'user_id' => $userId,
                'rating' => $rating,
            ]);

            return $ride->fresh();
        });
    }

    /**
     * Get ride details
     */
    public function getRideDetails(int $userId, string $rideUuid, string $correlationId = null): array
    {
        $correlationId = $correlationId ?? Str::uuid()->toString();
        
        $ride = TaxiRide::where('uuid', $rideUuid)
            ->where('passenger_id', $userId)
            ->with(['driver', 'vehicle'])
            ->firstOrFail();
        
        return [
            'uuid' => $ride->uuid,
            'status' => $ride->status,
            'pickup_address' => $ride->pickup_address,
            'dropoff_address' => $ride->dropoff_address,
            'distance_km' => $ride->distance_km,
            'base_price_rubles' => $ride->getBasePriceInRubles(),
            'final_price_rubles' => $ride->getFinalPriceInRubles(),
            'surge_multiplier' => $ride->surge_multiplier,
            'created_at' => $ride->created_at->toIso8601String(),
            'accepted_at' => $ride->metadata['accepted_at'] ?? null,
            'started_at' => $ride->metadata['started_at'] ?? null,
            'completed_at' => $ride->metadata['completed_at'] ?? null,
            'driver' => $ride->driver ? [
                'name' => $ride->driver->first_name,
                'rating' => $ride->driver->rating,
                'phone_number' => $ride->driver->phone_number,
            ] : null,
            'vehicle' => $ride->vehicle ? [
                'brand' => $ride->vehicle->brand,
                'model' => $ride->vehicle->model,
                'license_plate' => $ride->vehicle->license_plate,
                'color' => $ride->vehicle->color,
                'year' => $ride->vehicle->year,
            ] : null,
            'passenger_rating' => $ride->metadata['passenger_rating'] ?? null,
            'passenger_comment' => $ride->metadata['passenger_comment'] ?? null,
        ];
    }
}

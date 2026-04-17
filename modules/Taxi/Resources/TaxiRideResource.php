<?php declare(strict_types=1);

namespace Modules\Taxi\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;
use Modules\Taxi\Models\TaxiRide;
use Modules\Taxi\Models\TaxiDriver;
use Modules\Taxi\Models\TaxiVehicle;

/**
 * API Resource for Taxi Ride — Production Ready 2026.
 * 
 * Transforms TaxiRide model into consistent API response format.
 * Includes driver and vehicle details, pricing breakdown, status tracking,
 * and metadata for frontend rendering.
 * 
 * Follows CatVRF 2026 canon: consistent structure, correlation_id,
 * tenant isolation, null-safe transformations.
 */
final class TaxiRideResource extends JsonResource
{
    private const STATUS_LABELS = [
        TaxiRide::STATUS_REQUESTED => 'requested',
        TaxiRide::STATUS_ACCEPTED => 'accepted',
        TaxiRide::STATUS_STARTED => 'started',
        TaxiRide::STATUS_COMPLETED => 'completed',
        TaxiRide::STATUS_CANCELLED => 'cancelled',
    ];

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'status' => $this->status,
            'status_label' => self::STATUS_LABELS[$this->status] ?? $this->status,
            'correlation_id' => $this->correlation_id,
            
            'passenger' => [
                'id' => $this->passenger_id,
            ],
            
            'driver' => $this->when($this->driver_id !== null, function () {
                return [
                    'id' => $this->driver->id,
                    'uuid' => $this->driver->uuid,
                    'full_name' => $this->driver->full_name,
                    'rating' => $this->driver->rating,
                    'ride_count' => $this->driver->ride_count,
                    'current_location' => [
                        'latitude' => $this->driver->current_latitude,
                        'longitude' => $this->driver->current_longitude,
                        'last_update' => $this->driver->last_location_update?->toIso8601String(),
                    ],
                ];
            }),
            
            'vehicle' => $this->when($this->vehicle_id !== null, function () {
                return [
                    'id' => $this->vehicle->id,
                    'license_plate' => $this->vehicle->license_plate,
                    'make' => $this->vehicle->make,
                    'model' => $this->vehicle->model,
                    'year' => $this->vehicle->year,
                    'color' => $this->vehicle->color,
                    'vehicle_class' => $this->vehicle->vehicle_class,
                    'features' => $this->vehicle->features ?? [],
                ];
            }),
            
            'route' => [
                'pickup' => [
                    'address' => $this->pickup_address,
                    'latitude' => $this->pickup_latitude,
                    'longitude' => $this->pickup_longitude,
                ],
                'dropoff' => [
                    'address' => $this->dropoff_address,
                    'latitude' => $this->dropoff_latitude,
                    'longitude' => $this->dropoff_longitude,
                ],
                'distance' => [
                    'meters' => $this->distance_meters,
                    'kilometers' => round($this->distance_meters / 1000, 2),
                ],
                'duration' => [
                    'seconds' => $this->duration_seconds,
                    'minutes' => (int) ceil($this->duration_seconds / 60),
                ],
            ],
            
            'pricing' => [
                'base_price' => [
                    'kopeki' => $this->base_price_kopeki,
                    'rubles' => $this->base_price_kopeki / 100,
                ],
                'final_price' => [
                    'kopeki' => $this->final_price_kopeki,
                    'rubles' => $this->final_price_kopeki / 100,
                ],
                'surge_multiplier' => $this->surge_multiplier,
                'is_surge' => $this->surge_multiplier > 1.0,
                'currency' => 'RUB',
            ],
            
            'timestamps' => [
                'requested_at' => $this->requested_at?->toIso8601String(),
                'accepted_at' => $this->accepted_at?->toIso8601String(),
                'started_at' => $this->started_at?->toIso8601String(),
                'completed_at' => $this->completed_at?->toIso8601String(),
            ],
            
            'cancellation' => $this->when($this->status === TaxiRide::STATUS_CANCELLED, function () {
                return [
                    'reason' => $this->cancellation_reason,
                    'cancelled_at' => $this->completed_at?->toIso8601String(),
                ];
            }),
            
            'ratings' => [
                'passenger_rating' => $this->passenger_rating,
                'driver_rating' => $this->driver_rating,
            ],
            
            'metadata' => $this->metadata ?? [],
            
            'features' => [
                'is_b2b' => $this->metadata['is_b2b'] ?? false,
                'voice_order' => $this->metadata['voice_order'] ?? false,
                'biometric_verified' => $this->metadata['biometric_verified'] ?? false,
                'split_payment' => $this->metadata['split_payment'] ?? false,
                'split_payment_users' => $this->metadata['split_payment_users'] ?? [],
                'ar_navigation_enabled' => $this->metadata['ar_navigation_enabled'] ?? true,
                'video_call_requested' => $this->metadata['video_call_requested'] ?? false,
            ],
            
            'tracking' => [
                'tracking_enabled' => in_array($this->status, [TaxiRide::STATUS_ACCEPTED, TaxiRide::STATUS_STARTED]),
                'tracking_url' => in_array($this->status, [TaxiRide::STATUS_ACCEPTED, TaxiRide::STATUS_STARTED])
                    ? url("/api/taxi/rides/{$this->id}/track")
                    : null,
                'video_call_url' => ($this->metadata['video_call_requested'] ?? false) && $this->status === TaxiRide::STATUS_ACCEPTED
                    ? url("/api/taxi/rides/{$this->id}/video-call")
                    : null,
            ],
            
            'actions' => $this->getAvailableActions(),
        ];
    }

    private function getAvailableActions(): array
    {
        $actions = [];

        match ($this->status) {
            TaxiRide::STATUS_REQUESTED => [
                'can_cancel' => true,
                'can_modify' => true,
            ],
            TaxiRide::STATUS_ACCEPTED => [
                'can_cancel' => true,
                'can_track' => true,
                'can_video_call' => $this->metadata['video_call_requested'] ?? false,
            ],
            TaxiRide::STATUS_STARTED => [
                'can_track' => true,
                'can_rate' => false,
            ],
            TaxiRide::STATUS_COMPLETED => [
                'can_rate' => true,
                'can_reorder' => true,
            ],
            TaxiRide::STATUS_CANCELLED => [
                'can_reorder' => true,
            ],
            default => [],
        };

        return $actions;
    }

    public static function collection($resource)
    {
        return parent::collection($resource)->additional([
            'meta' => [
                'correlation_id' => Str::uuid()->toString(),
            ],
        ]);
    }
}

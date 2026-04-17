<?php declare(strict_types=1);

namespace App\Domains\Taxi\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * TaxiRideResource - Full trip details with driver, vehicle, pricing
 * Classic taxi-style trip card like Yandex.Taxi
 */
final class TaxiRideResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'status' => $this->status,
            'status_name' => $this->getStatusName($this->status),
            'passenger' => [
                'id' => $this->passenger_id,
                'name' => $this->passenger?->name,
                'phone' => $this->passenger?->phone ? $this->maskPhone($this->passenger->phone) : null,
                'avatar_url' => $this->passenger?->avatar_url,
                'rating' => (float) ($this->passenger?->rating ?? 0),
            ],
            'driver' => $this->when($this->driver_id !== null, function () {
                return [
                    'id' => $this->driver_id,
                    'name' => $this->driver?->name,
                    'photo_url' => $this->driver?->photo_url,
                    'rating' => (float) ($this->driver?->rating ?? 0),
                    'phone' => $this->driver?->phone ? $this->maskPhone($this->driver->phone) : null,
                ];
            }),
            'vehicle' => $this->when($this->vehicle_id !== null, function () {
                return [
                    'id' => $this->vehicle_id,
                    'plate_number' => $this->vehicle?->plate_number,
                    'plate_number_formatted' => $this->vehicle?->plate_number,
                    'brand' => $this->vehicle?->brand,
                    'model' => $this->vehicle?->model,
                    'color' => $this->vehicle?->color,
                    'photo_url' => $this->vehicle?->photo_url,
                    'vehicle_class' => $this->vehicle?->vehicle_class,
                ];
            }),
            'route' => [
                'pickup' => [
                    'address' => $this->pickup_address,
                    'lat' => (float) $this->pickup_lat,
                    'lon' => (float) $this->pickup_lon,
                ],
                'dropoff' => [
                    'address' => $this->dropoff_address,
                    'lat' => (float) $this->dropoff_lat,
                    'lon' => (float) $this->dropoff_lon,
                ],
                'distance_km' => (float) $this->distance_km,
                'estimated_minutes' => (int) $this->estimated_minutes,
                'actual_distance_km' => $this->actual_distance_km !== null ? (float) $this->actual_distance_km : null,
            ],
            'pricing' => [
                'base_price' => (int) $this->base_price,
                'surge_multiplier' => (float) $this->surge_multiplier,
                'total_price' => (int) $this->total_price,
                'final_price' => $this->final_price !== null ? (int) $this->final_price : null,
                'platform_commission' => (int) $this->platform_commission,
                'fleet_commission' => (int) $this->fleet_commission,
                'cancellation_fee' => $this->cancellation_fee !== null ? (int) $this->cancellation_fee : null,
                'price_breakdown' => $this->metadata['pricing_breakdown'] ?? [],
            ],
            'timing' => [
                'created_at' => $this->created_at->toIso8601String(),
                'assigned_at' => $this->assigned_at?->toIso8601String(),
                'started_at' => $this->started_at?->toIso8601String(),
                'completed_at' => $this->completed_at?->toIso8601String(),
                'cancelled_at' => $this->cancelled_at?->toIso8601String(),
                'predicted_eta' => $this->predicted_eta !== null ? (int) $this->predicted_eta : null,
            ],
            'payment' => [
                'method' => $this->payment_method,
                'is_split_payment' => (bool) $this->is_split_payment,
                'split_details' => $this->split_payment_details,
                'payment_status' => $this->payment_status ?? 'pending',
            ],
            'options' => [
                'voice_order_enabled' => (bool) $this->voice_order_enabled,
                'biometric_auth_required' => (bool) $this->biometric_auth_required,
                'video_call_enabled' => (bool) $this->video_call_enabled,
            ],
            'rating' => [
                'driver_rating' => $this->driver_rating !== null ? (int) $this->driver_rating : null,
                'passenger_rating' => $this->passenger_rating !== null ? (int) $this->passenger_rating : null,
                'comment' => $this->rating_comment,
            ],
            'cancellation' => $this->when($this->status === 'cancelled', function () {
                return [
                    'cancelled_by' => $this->cancelled_by,
                    'reason' => $this->cancellation_reason,
                    'fee' => (int) $this->cancellation_fee,
                ];
            }),
            'metadata' => $this->metadata,
            'correlation_id' => $this->correlation_id,
        ];
    }

    private function getStatusName(string $status): string
    {
        return match($status) {
            'pending' => 'Поиск водителя',
            'driver_assigned' => 'Водитель найден',
            'in_progress' => 'В поездке',
            'completed' => 'Завершена',
            'cancelled' => 'Отменена',
            'no_drivers_available' => 'Водителей нет',
            default => 'Неизвестно',
        };
    }

    private function maskPhone(string $phone): string
    {
        return preg_replace('/(\d{1})(\d{3})(\d{3})(\d{2})(\d{2})/', '+$1 ($2) $3-$4-$5', $phone);
    }
}

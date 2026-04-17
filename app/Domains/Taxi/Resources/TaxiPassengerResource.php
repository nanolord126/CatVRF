<?php declare(strict_types=1);

namespace App\Domains\Taxi\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * TaxiPassengerResource - Passenger profile card
 * Classic taxi-style passenger profile like Yandex.Taxi
 */
final class TaxiPassengerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'name' => $this->name,
            'surname' => $this->surname,
            'patronymic' => $this->patronymic,
            'full_name' => trim("{$this->surname} {$this->name} {$this->patronymic}"),
            'avatar_url' => $this->avatar_url,
            'avatar_thumbnail_url' => $this->avatar_thumbnail_url,
            'phone' => $this->phone ? $this->maskPhone($this->phone) : null,
            'email' => $this->email,
            'rating' => (float) ($this->rating ?? 0),
            'rating_count' => (int) ($this->rating_count ?? 0),
            'verification_status' => $this->verification_status,
            'is_verified' => $this->verification_status === 'verified',
            'is_b2b_user' => $this->business_group_id !== null,
            'business_group_id' => $this->business_group_id,
            'business_card_id' => $this->business_card_id,
            'stats' => [
                'total_rides' => (int) ($this->total_rides ?? 0),
                'rides_this_month' => (int) ($this->rides_this_month ?? 0),
                'total_spent' => (int) ($this->total_spent ?? 0),
                'spent_this_month' => (int) ($this->spent_this_month ?? 0),
                'cancelled_rides' => (int) ($this->cancelled_rides ?? 0),
                'completion_rate' => (float) ($this->completion_rate ?? 0.95),
            ],
            'preferences' => [
                'favorite_tariff' => $this->favorite_tariff,
                'favorite_drivers' => $this->favorite_drivers ?? [],
                'saved_addresses' => $this->saved_addresses ?? [],
                'payment_methods' => $this->payment_methods ?? [],
                'default_payment_method' => $this->default_payment_method,
                'notifications_enabled' => (bool) ($this->notifications_enabled ?? true),
                'marketing_notifications' => (bool) ($this->marketing_notifications ?? false),
                'voice_order_enabled' => (bool) ($this->voice_order_enabled ?? false),
                'biometric_auth_enabled' => (bool) ($this->biometric_auth_enabled ?? false),
            ],
            'loyalty' => [
                'loyalty_level' => $this->loyalty_level ?? 'bronze',
                'loyalty_points' => (int) ($this->loyalty_points ?? 0),
                'bonuses_balance' => (int) ($this->bonuses_balance ?? 0),
                'next_level_points' => (int) ($this->next_level_points ?? 1000),
                'discount_percentage' => (float) ($this->loyalty_discount_percentage ?? 0),
            ],
            'wallet' => [
                'balance' => (int) ($this->wallet_balance ?? 0),
                'currency' => 'RUB',
                'is_active' => (bool) ($this->wallet_active ?? true),
            ],
            'recent_rides' => $this->when($this->relationLoaded('recentRides'), function () {
                return TaxiRideResource::collection($this->recentRides);
            }),
            'favorite_places' => [
                'home' => $this->home_address,
                'work' => $this->work_address,
                'other' => $this->other_addresses ?? [],
            ],
            'created_at' => $this->created_at->toIso8601String(),
            'last_ride_at' => $this->last_ride_at?->toIso8601String(),
            'last_active_at' => $this->last_active_at?->toIso8601String(),
        ];
    }

    private function maskPhone(string $phone): string
    {
        return preg_replace('/(\d{1})(\d{3})(\d{3})(\d{2})(\d{2})/', '+$1 ($2) $3-$4-$5', $phone);
    }
}

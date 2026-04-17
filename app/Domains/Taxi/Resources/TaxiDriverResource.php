<?php declare(strict_types=1);

namespace App\Domains\Taxi\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Domains\Taxi\Models\TaxiDriver;
use App\Domains\Taxi\Models\TaxiVehicle;

/**
 * TaxiDriverResource - Driver card with photo, car data, rating, stats
 * Classic taxi-style driver profile like Yandex.Taxi
 */
final class TaxiDriverResource extends JsonResource
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
            'photo_url' => $this->photo_url,
            'photo_thumbnail_url' => $this->photo_thumbnail_url,
            'phone' => $this->phone ? $this->maskPhone($this->phone) : null,
            'rating' => (float) $this->rating,
            'rating_count' => (int) $this->rating_count,
            'verification_status' => $this->verification_status,
            'is_verified' => $this->verification_status === 'verified',
            'is_online' => (bool) $this->is_online,
            'status' => $this->status,
            'experience_years' => $this->experience_years,
            'total_rides' => (int) ($this->total_rides ?? 0),
            'completion_rate' => (float) ($this->completion_rate ?? 0.95),
            'acceptance_rate' => (float) ($this->acceptance_rate ?? 0.9),
            'current_streak' => (int) ($this->current_streak ?? 0),
            'max_streak' => (int) ($this->max_streak ?? 0),
            'badges' => $this->getBadges(),
            'vehicle' => $this->when($this->relationLoaded('vehicles') && $this->vehicles->isNotEmpty(), function () {
                return new TaxiVehicleResource($this->vehicles->first());
            }),
            'current_location' => [
                'lat' => (float) $this->current_lat,
                'lon' => (float) $this->current_lon,
                'updated_at' => $this->location_updated_at?->toIso8601String(),
            ],
            'stats' => [
                'rides_today' => $this->rides_today ?? 0,
                'rides_this_week' => $this->rides_this_week ?? 0,
                'earnings_today' => $this->earnings_today ?? 0,
                'earnings_this_week' => $this->earnings_this_week ?? 0,
                'online_hours_today' => $this->online_hours_today ?? 0,
            ],
            'preferences' => [
                'music_genres' => $this->music_genres ?? [],
                'conversation_level' => $this->conversation_level ?? 'neutral',
                'smoking_allowed' => (bool) ($this->smoking_allowed ?? false),
                'pets_allowed' => (bool) ($this->pets_allowed ?? false),
                'air_conditioner' => (bool) ($this->air_conditioner ?? true),
                'child_seat_available' => (bool) ($this->child_seat_available ?? false),
                'wifi_available' => (bool) ($this->wifi_available ?? false),
                'charger_available' => (bool) ($this->charger_available ?? false),
                'water_available' => (bool) ($this->water_available ?? false),
            ],
            'languages' => $this->languages ?? ['ru'],
            'about' => $this->about,
            'created_at' => $this->created_at->toIso8601String(),
            'last_active_at' => $this->last_active_at?->toIso8601String(),
        ];
    }

    private function maskPhone(string $phone): string
    {
        return preg_replace('/(\d{1})(\d{3})(\d{3})(\d{2})(\d{2})/', '+$1 ($2) $3-$4-$5', $phone);
    }

    private function getBadges(): array
    {
        $badges = [];
        
        if (($this->rating ?? 0) >= 4.9) {
            $badges[] = [
                'code' => 'top_driver',
                'name' => 'Топ водитель',
                'icon' => 'star',
                'color' => 'gold',
            ];
        }
        
        if (($this->total_rides ?? 0) >= 1000) {
            $badges[] = [
                'code' => 'experienced',
                'name' => 'Опытный',
                'icon' => 'medal',
                'color' => 'silver',
            ];
        }
        
        if (($this->completion_rate ?? 0) >= 0.98) {
            $badges[] = [
                'code' => 'reliable',
                'name' => 'Надежный',
                'icon' => 'shield',
                'color' => 'green',
            ];
        }
        
        if (($this->current_streak ?? 0) >= 10) {
            $badges[] = [
                'code' => 'streak_master',
                'name' => 'Мастер стрика',
                'icon' => 'fire',
                'color' => 'orange',
            ];
        }

        return $badges;
    }

    public static function collection($resource)
    {
        return parent::collection($resource);
    }
}

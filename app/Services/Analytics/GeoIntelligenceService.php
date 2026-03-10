<?php

namespace App\Services\Analytics;

use App\Models\Gym;
use App\Models\Event;
use Illuminate\Support\Collection;

class GeoIntelligenceService
{
    /**
     * Generates heatmaps for occupancy and load data.
     */
    public function getOccupancyHeatmap(string $vertical): Collection
    {
        return match($vertical) {
            'sports' => Gym::all()->map(fn($gym) => [
                'name' => $gym->name,
                'coords' => $gym->geo_location,
                'occupancy' => $gym->occupancy_data,
                'current_load' => $this->calculateDynamicLoad($gym->occupancy_data)
            ]),
            'events' => Event::where('status', 'published')
                ->with('venue')
                ->get()
                ->map(fn($event) => [
                    'name' => $event->title,
                    'coords' => $event->venue->geo_location,
                    'is_high_demand' => $event->tickets()->sum('quantity_available') < 10,
                ]),
            default => collect([])
        };
    }

    private function calculateDynamicLoad(array $data): int
    {
        // Marketplace 2026 logic for load prediction
        $dayNum = now()->dayOfWeek - 1; 
        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        $dayName = $days[$dayNum] ?? 'monday';

        $hour = now()->format('H:00');
        return (int) ($data[$dayName][$hour] ?? 0);
    }
}

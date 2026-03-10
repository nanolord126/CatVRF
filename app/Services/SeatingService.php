<?php

namespace App\Services;

use App\Models\Venue;
use Illuminate\Support\Collection;

class SeatingService
{
    /**
     * Map Konva.js UI elements to SQLite/Database backend.
     * Logic for sectors (A, B, VIP) and tier Pricing.
     */
    public function getInteractiveMap(Venue $venue): array
    {
        return [
            'layout' => json_decode($venue->hall_layout, true),
            'sectors' => $this->calculateOccupancy($venue),
            'konva_config' => [
                'stageWidth' => 1200,
                'stageHeight' => 800,
                'theme' => 'dark_2026',
            ],
            'available_seats_count' => $venue->capacity - $this->getBookedCount($venue),
        ];
    }

    private function calculateOccupancy(Venue $v): Collection
    {
        return collect(['VIP', 'Sector A', 'Sector B'])->mapWithKeys(function ($s) use ($v) {
            return [$s => rand(10, 50)]; // Simulation for dashboard/heatmap
        });
    }

    private function getBookedCount(Venue $v): int { return 120; }
}

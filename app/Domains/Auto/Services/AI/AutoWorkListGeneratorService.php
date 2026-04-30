<?php declare(strict_types=1);

namespace App\Domains\Auto\Services\AI;

use Illuminate\Support\Str;

/**
 * AutoWorkListGeneratorService - Generates work list from damage detection
 * 
 * Creates repair work items with estimated hours and prices based on damage severity.
 */
final readonly class AutoWorkListGeneratorService
{
    /**
     * Generate work list from damage detection
     */
    public function generate(array $damageDetection, array $vinDecoding, bool $isB2b): array
    {
        $workItems = [];

        foreach ($damageDetection['damages'] as $damage) {
            $severity = $damage['severity'] ?? 'low';
            $estimatedHours = match ($severity) {
                'low' => 1,
                'medium' => 3,
                'high' => 6,
                default => 2,
            };

            $basePrice = match ($severity) {
                'low' => 5000,
                'medium' => 15000,
                'high' => 35000,
                default => 10000,
            };

            $workItems[] = [
                'id' => Str::uuid()->toString(),
                'location' => $damage['location'] ?? 'Unknown',
                'type' => $damage['type'] ?? 'Repair',
                'description' => $damage['description'] ?? 'Damage repair',
                'severity' => $severity,
                'estimated_hours' => $estimatedHours,
                'price' => $isB2b ? $basePrice * 0.85 : $basePrice,
                'priority' => $severity === 'high' ? 'urgent' : 'normal',
            ];
        }

        // Add comprehensive inspection if condition is low
        if ($damageDetection['overall_condition'] < 6) {
            $workItems[] = [
                'id' => Str::uuid()->toString(),
                'location' => 'Full Vehicle',
                'type' => 'Comprehensive Inspection',
                'description' => 'Detailed mechanical and electrical inspection due to low overall condition',
                'severity' => 'medium',
                'estimated_hours' => 4,
                'price' => $isB2b ? 12000 : 15000,
                'priority' => 'high',
            ];
        }

        return $workItems;
    }
}

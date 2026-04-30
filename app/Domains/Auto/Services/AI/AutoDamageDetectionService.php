<?php declare(strict_types=1);

namespace App\Domains\Auto\Services\AI;

/**
 * AutoDamageDetectionService - Analyzes damage from vision analysis
 * 
 * Processes vision analysis results to detect and categorize damage.
 */
final readonly class AutoDamageDetectionService
{
    /**
     * Detect damages from vision analysis
     */
    public function detect(array $visionAnalysis): array
    {
        $damages = $visionAnalysis['damages'] ?? [];
        $criticalDamages = array_filter($damages, fn($damage) => ($damage['severity'] ?? 'low') === 'high');

        return [
            'damages' => $damages,
            'total_count' => count($damages),
            'critical_count' => count($criticalDamages),
            'requires_immediate_attention' => count($criticalDamages) > 0,
            'overall_condition' => $visionAnalysis['overall_condition'] ?? 8,
        ];
    }
}

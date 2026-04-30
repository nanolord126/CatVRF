<?php declare(strict_types=1);

namespace App\Domains\Auto\Services\AI;

/**
 * AutoDataAnonymizerService - Anonymizes auto-related data for external AI calls
 * 
 * Ensures VIN and other sensitive identifiers are anonymized before external API calls.
 */
final readonly class AutoDataAnonymizerService
{
    /**
     * Anonymize VIN - keep first 3 and last 4 characters
     */
    public function anonymizeVIN(string $vin): string
    {
        if (strlen($vin) < 7) {
            return '***';
        }
        return substr($vin, 0, 3) . str_repeat('*', strlen($vin) - 7) . substr($vin, -4);
    }

    /**
     * Anonymize license plate
     */
    public function anonymizeLicensePlate(string $plate): string
    {
        if (strlen($plate) < 4) {
            return '***';
        }
        return substr($plate, 0, 2) . str_repeat('*', strlen($plate) - 4) . substr($plate, -2);
    }
}

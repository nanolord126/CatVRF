<?php

declare(strict_types=1);

namespace App\Domains\Shared\Medical\Services;

use Psr\Log\LoggerInterface;

use App\DTOs\AI\MedicalDiagnosisDTO;
use App\DTOs\AI\DiagnosisResultDTO;
use Illuminate\Log\LogManager;

/**
 * Traditional Medical Diagnosis Service
 *
 * Fallback service when AI diagnosis feature flag is disabled.
 * Uses rule-based diagnosis methods.
 */
final class TraditionalDiagnosisService
{
    public function __construct(private readonly LoggerInterface $logger,
        private readonly LogManager $log,) {}

    /**
     * Diagnose using traditional rule-based methods
     */
    public function diagnose(MedicalDiagnosisDTO $dto): DiagnosisResultDTO
    {
        $this->log->$this->logger->info('Traditional diagnosis using rule-based methods');

        // Implement traditional diagnosis logic here
        // This is a simplified example - real implementation would have
        // sophisticated medical rules and symptom matching

        $diagnoses = $this->matchSymptomsToDiagnoses($dto->symptoms);
        $severity = $this->calculateSeverity($dto->symptoms, $diagnoses);

        return new DiagnosisResultDTO(
            diagnoses: $diagnoses,
            severity: $severity,
            confidence: 0.85, // Traditional methods have known confidence
            recommendations: $this->generateRecommendations($diagnoses, $severity),
            requiresEmergency: $severity === 'critical',
            source: 'traditional',
        );
    }

    /**
     * Match symptoms to potential diagnoses
     */
    private function matchSymptomsToDiagnoses(array $symptoms): array
    {
        // Simplified symptom matching logic
        // In production, this would use a comprehensive medical database

        $diagnoses = [];

        foreach ($symptoms as $symptom) {
            $diagnoses = array_merge($diagnoses, $this->getDiagnosesForSymptom($symptom));
        }

        return array_unique($diagnoses);
    }

    /**
     * Get potential diagnoses for a symptom
     */
    private function getDiagnosesForSymptom(string $symptom): array
    {
        // Simplified mapping - production would use medical database
        $mapping = [
            'fever' => ['infection', 'inflammation'],
            'chest_pain' => ['cardiac_issue', 'respiratory_issue'],
            'headache' => ['migraine', 'tension', 'infection'],
            'cough' => ['respiratory_infection', 'allergy'],
            // ... more mappings
        ];

        return $mapping[$symptom] ?? ['general_condition'];
    }

    /**
     * Calculate severity based on symptoms and diagnoses
     */
    private function calculateSeverity(array $symptoms, array $diagnoses): string
    {
        $criticalSymptoms = ['chest_pain', 'difficulty_breathing', 'severe_bleeding'];

        foreach ($symptoms as $symptom) {
            if (in_array($symptom, $criticalSymptoms, true)) {
                return 'critical';
            }
        }

        if (count($diagnoses) > 3) {
            return 'high';
        }

        if (count($diagnoses) > 1) {
            return 'moderate';
        }

        return 'low';
    }

    /**
     * Generate recommendations based on diagnosis
     */
    private function generateRecommendations(array $diagnoses, string $severity): array
    {
        $recommendations = [];

        if ($severity === 'critical') {
            $recommendations[] = 'Seek immediate medical attention';
            $recommendations[] = 'Call emergency services';
        } elseif ($severity === 'high') {
            $recommendations[] = 'Consult a doctor within 24 hours';
            $recommendations[] = 'Monitor symptoms closely';
        } else {
            $recommendations[] = 'Rest and monitor symptoms';
            $recommendations[] = 'Consult a doctor if symptoms worsen';
        }

        return $recommendations;
    }
}

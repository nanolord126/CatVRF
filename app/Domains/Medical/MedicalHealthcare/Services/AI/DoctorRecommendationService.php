<?php declare(strict_types=1);

namespace App\Domains\Medical\MedicalHealthcare\Services\AI;

use App\Domains\Medical\MedicalHealthcare\DTOs\AIDiagnosticResultDto;
use App\Domains\Medical\Models\Doctor;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Facades\Log;

/**
 * DoctorRecommendationService - Recommends doctors based on diagnosis
 * 
 * Matches patients with appropriate doctors based on diagnosis, location, and availability.
 */
final readonly class DoctorRecommendationService
{
    public function __construct(
        private readonly DatabaseManager $db,
    ) {}

    /**
     * Recommend doctors based on diagnosis
     * 
     * @return array Recommended doctors with match scores
     */
    public function recommendDoctors(AIDiagnosticResultDto $diagnosis, int $tenantId, ?float $latitude = null, ?float $longitude = null): array
    {
        $specialties = $this->extractSpecialtiesFromDiagnosis($diagnosis);
        
        $query = $this->db->table('doctors')
            ->join('clinics', 'doctors.clinic_id', '=', 'clinics.id')
            ->where('doctors.tenant_id', $tenantId)
            ->where('doctors.is_active', true)
            ->whereIn('doctors.specialty', $specialties)
            ->select('doctors.*', 'clinics.name as clinic_name', 'clinics.latitude', 'clinics.longitude');

        // Add distance calculation if coordinates provided
        if ($latitude !== null && $longitude !== null) {
            $query->selectRaw(
                '(6371 * acos(cos(radians(?)) * cos(radians(clinics.latitude)) * cos(radians(clinics.longitude) - radians(?)) + sin(radians(?)) * sin(radians(clinics.latitude)))) AS distance',
                [$latitude, $longitude, $latitude]
            );
            $query->having('distance', '<', 50);
            $query->orderBy('distance');
        }

        $doctors = $query->limit(5)->get();

        $recommendations = [];
        foreach ($doctors as $doctor) {
            $recommendations[] = [
                'id' => $doctor->id,
                'name' => $doctor->name,
                'specialty' => $doctor->specialty,
                'clinic_name' => $doctor->clinic_name,
                'rating' => $doctor->rating ?? 0,
                'experience_years' => $doctor->experience_years ?? 0,
                'distance_km' => $doctor->distance ?? null,
                'match_score' => $this->calculateMatchScore($doctor, $diagnosis),
            ];
        }

        Log::info('doctor_recommendations.generated', [
            'count' => count($recommendations),
            'diagnosis_urgency' => $diagnosis->urgencyLevel,
        ]);

        return $recommendations;
    }

    /**
     * Extract relevant medical specialties from diagnosis
     */
    private function extractSpecialtiesFromDiagnosis(AIDiagnosticResultDto $diagnosis): array
    {
        $specialtiesMap = [
            'cardiologist' => ['heart', 'cardiac', 'chest pain', 'palpitation'],
            'neurologist' => ['headache', 'migraine', 'dizziness', 'numbness'],
            'dermatologist' => ['skin', 'rash', 'itching'],
            'gastroenterologist' => ['stomach', 'digestive', 'nausea', 'vomiting'],
            'pulmonologist' => ['breathing', 'cough', 'lung'],
            'therapist' => ['general', 'fever', 'fatigue'],
        ];

        $matchedSpecialties = ['therapist']; // Default
        $diagnosisText = strtolower(json_encode($diagnosis->diagnoses));

        foreach ($specialtiesMap as $specialty => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($diagnosisText, $keyword)) {
                    $matchedSpecialties[] = $specialty;
                    break;
                }
            }
        }

        return array_unique($matchedSpecialties);
    }

    /**
     * Calculate match score for doctor recommendation
     */
    private function calculateMatchScore(object $doctor, AIDiagnosticResultDto $diagnosis): float
    {
        $score = 0.5; // Base score

        // Rating component
        $score += ($doctor->rating ?? 0) / 10 * 0.3;

        // Experience component
        $score += min(($doctor->experience_years ?? 0) / 20, 1) * 0.2;

        return min($score, 1.0);
    }
}

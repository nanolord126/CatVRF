<?php declare(strict_types=1);

namespace App\Domains\Shared\Medical\MedicalHealthcare\Services\AI;

use App\Domains\Shared\Medical\MedicalHealthcare\DTOs\AIDiagnosticRequestDto;
use App\Domains\Shared\Medical\MedicalHealthcare\DTOs\AIDiagnosticResultDto;
use App\Domains\Shared\Medical\MedicalHealthcare\DTOs\HealthScorePredictionDto;
use App\Domains\Shared\Medical\MedicalHealthcare\Events\EmergencyDetectedEvent;
use Illuminate\Contracts\Events\Dispatcher as EventDispatcher;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * HealthcareAIDiagnosticServiceRefactored - Orchestrates AI healthcare services
 * 
 * This service orchestrates the smaller, focused AI healthcare services.
 * It no longer contains business logic, but delegates to specialized services.
 */
final readonly class HealthcareAIDiagnosticServiceRefactored
{
    public function __construct(
        private readonly AIDiagnosticCoreService $aiDiagnostic,
        private readonly HealthScorePredictionService $healthScorePrediction,
        private readonly DoctorRecommendationService $doctorRecommendation,
        private readonly AppointmentSlotService $appointmentSlot,
        private readonly VideoConsultationService $videoConsultation,
        private readonly InstantCheckInService $instantCheckIn,
        private readonly EmergencyProtocolService $emergencyProtocol,
        private readonly EventDispatcher $eventDispatcher,
        private readonly DatabaseManager $db,
    ) {}

    /**
     * Analyze symptoms and provide diagnosis
     */
    public function analyzeSymptomsAndDiagnose(AIDiagnosticRequestDto $dto): AIDiagnosticResultDto
    {
        $result = $this->aiDiagnostic->analyzeSymptoms($dto);

        // Trigger emergency protocol if needed
        if ($result->requiresEmergency) {
            $this->emergencyProtocol->trigger($dto->userId, $result, $dto->correlationId);
        }

        return $result;
    }

    /**
     * Predict health score trend
     */
    public function predictHealthScore(int $userId, string $correlationId): HealthScorePredictionDto
    {
        return $this->healthScorePrediction->predictHealthScore($userId, $correlationId);
    }

    /**
     * Recommend doctors based on diagnosis
     */
    public function recommendDoctors(AIDiagnosticResultDto $diagnosis, int $tenantId, ?float $latitude = null, ?float $longitude = null): array
    {
        return $this->doctorRecommendation->recommendDoctors($diagnosis, $tenantId, $latitude, $longitude);
    }

    /**
     * Hold appointment slot
     */
    public function holdAppointmentSlot(int $userId, int $doctorId, string $dateTime, string $correlationId, bool $extended = false): array
    {
        return $this->appointmentSlot->holdSlot($userId, $doctorId, $dateTime, $correlationId, $extended);
    }

    /**
     * Release appointment slot
     */
    public function releaseAppointmentSlot(int $doctorId, string $dateTime, string $correlationId): void
    {
        $this->appointmentSlot->releaseSlot($doctorId, $dateTime, $correlationId);
    }

    /**
     * Generate video consultation token
     */
    public function generateVideoConsultationToken(int $appointmentId, int $userId, string $correlationId): array
    {
        return $this->videoConsultation->generateToken($appointmentId, $userId, $correlationId);
    }

    /**
     * Check in patient
     */
    public function checkIn(int $appointmentId, string $nfcData = '', string $qrCode = '', string $correlationId = ''): array
    {
        return $this->instantCheckIn->checkIn($appointmentId, $nfcData, $qrCode, $correlationId);
    }
}

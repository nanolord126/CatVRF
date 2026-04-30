<?php declare(strict_types=1);

namespace App\Domains\Medical\MedicalHealthcare\Services\AI;

use App\Domains\Medical\MedicalHealthcare\DTOs\AIDiagnosticResultDto;
use App\Domains\Medical\MedicalHealthcare\Events\EmergencyDetectedEvent;
use Illuminate\Contracts\Events\Dispatcher as EventDispatcher;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * EmergencyProtocolService - Handles emergency situations
 * 
 * Triggers emergency protocols when critical health issues are detected.
 */
final readonly class EmergencyProtocolService
{
    private const EMERGENCY_THRESHOLD = 30;

    public function __construct(
        private readonly EventDispatcher $eventDispatcher,
    ) {}

    /**
     * Trigger emergency protocol for critical cases
     */
    public function trigger(int $userId, AIDiagnosticResultDto $diagnosis, string $correlationId): void
    {
        Log::critical('emergency.protocol.triggered', [
            'user_id' => $userId,
            'correlation_id' => $correlationId,
            'health_score' => $diagnosis->healthScore,
            'urgency_level' => $diagnosis->urgencyLevel,
        ]);

        // Dispatch emergency event for async handling
        $this->eventDispatcher->dispatch(
            new EmergencyDetectedEvent(
                userId: $userId,
                diagnosis: $diagnosis,
                correlationId: $correlationId,
                timestamp: now(),
            )
        );
    }

    /**
     * Check if diagnosis requires emergency protocol
     */
    public function requiresEmergency(AIDiagnosticResultDto $diagnosis): bool
    {
        return $diagnosis->healthScore < self::EMERGENCY_THRESHOLD 
            || $diagnosis->urgencyLevel === 'emergency';
    }
}

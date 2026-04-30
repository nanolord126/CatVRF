<?php

declare(strict_types=1);

namespace Modules\Analytics\Application\Services;

use Modules\Analytics\Application\DTOs\BehavioralEventDto;
use Modules\Analytics\Application\DTOs\RFMScoreDto;
use Modules\Analytics\Application\UseCases\CaptureBehavioralEventUseCase;
use Modules\Analytics\Application\UseCases\CalculateRFMScoreUseCase;
use Modules\Analytics\Domain\Entities\BehavioralEvent;
use App\Traits\WithAuditLogging;
use App\Services\Security\AuditService;

final readonly class AnalyticsOrchestratorService
{
    use WithAuditLogging;

    public function __construct(
        private CaptureBehavioralEventUseCase $captureEventUseCase,
        private CalculateRFMScoreUseCase $calculateRFMUseCase,
        private readonly AuditService $auditService,
    ) {
    }

    public function captureEvent(BehavioralEventDto $dto): BehavioralEvent
    {
        $event = $this->captureEventUseCase->execute($dto);
        
        $this->logAction(
            action: 'behavioral_event_captured',
            entityType: 'BehavioralEvent',
            entityId: $event->id ?? null,
            context: [
                'user_id' => $dto->userId,
                'event_type' => $dto->eventType,
                'correlation_id' => $dto->correlationId ?? null,
            ],
            userId: $dto->userId,
            tenantId: null
        );
        
        return $event;
    }

    public function calculateRFM(int $userId): array
    {
        $rfmScore = $this->calculateRFMUseCase->execute($userId);
        
        $this->logAction(
            action: 'rfm_score_calculated',
            entityType: 'User',
            entityId: $userId,
            context: [
                'recency' => $rfmScore['recency'] ?? null,
                'frequency' => $rfmScore['frequency'] ?? null,
                'monetary' => $rfmScore['monetary'] ?? null,
            ],
            userId: $userId,
            tenantId: null
        );
        
        return $rfmScore;
    }

    public function captureEventAndCalculateRFM(BehavioralEventDto $dto): array
    {
        $event = $this->captureEvent($dto);

        // Only calculate RFM for purchase-related events
        if (in_array($dto->eventType, ['order_completed', 'payment_successful'], true)) {
            $rfmScore = $this->calculateRFM($dto->userId);
            return [
                'event' => $event,
                'rfm_score' => $rfmScore,
            ];
        }

        return [
            'event' => $event,
        ];
    }
}

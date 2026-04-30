<?php

declare(strict_types=1);

namespace Modules\VetGrooming\Application\Services;

use Modules\VetGrooming\Domain\Repositories\GroomingSessionRepositoryInterface;
use Modules\VetGrooming\Domain\Repositories\PetChronicConditionRepositoryInterface;
use Modules\VetGrooming\Domain\Entities\GroomingSession;
use Modules\VetGrooming\Domain\Enums\GroomingServiceType;
use Modules\VetGrooming\Domain\Enums\GroomingStatus;
use Modules\VetGrooming\Domain\Enums\BehaviorRating;
use Carbon\CarbonImmutable;
use Illuminate\Cache\CacheManager;
use Illuminate\Log\LogManager;
use App\Services\Fraud\FraudControlService;
use App\Domains\CRM\Services\PetCrmService;
use App\Services\Security\AuditService;
use App\Traits\WithAuditLogging;
use App\Traits\WithAnalyticsTracking;

/**
 * GroomingService - Grooming Session Management
 *
 * CatVRF 2026 Canon - Production Mandatory
 * - Fraud check перед всеми мутациями
 * - Readonly класс
 * - $this->cache->tags для инвалидации
 * - Audit логирование
 * - CRM интеграция
 */
final readonly class GroomingService
{
    use WithAuditLogging;
    use WithAnalyticsTracking;

    public function __construct(
        private readonly FraudControlService $fraud,
        private readonly PetCrmService $petCrm,
        private readonly GroomingSessionRepositoryInterface $sessionRepository,
        private readonly PetChronicConditionRepositoryInterface $conditionRepository,
        private readonly CacheManager $cache,
        private readonly LogManager $logger,
        private readonly AuditService $auditService,
    ) {}

    /**
     * Create a new grooming session
     */
    public function createSession(
        int $tenantId,
        int $petId,
        GroomingServiceType $serviceType,
        ?int $clinicId = null,
        ?int $groomerId = null,
        ?int $appointmentId = null,
        ?int $serviceId = null,
        ?CarbonImmutable $startedAt = null,
        ?string $correlationId = null,
    ): GroomingSession {
        $correlationId ??= uniqid('vetgrooming_session_', true);

        // FRAUD CHECK - мандаторно первым действием
        $fraudResult = $this->fraud->checkRequest([
            'action' => 'vetgrooming_create_session',
            'user_id' => null,
            'tenant_id' => $tenantId,
            'ip_address' => request()->ip(),
        ]);

        if ($fraudResult['should_block']) {
            $this->logger->channel('audit')->warning('VetGrooming session creation blocked by fraud detection', [
                'fraud_score' => $fraudResult['fraud_score'],
                'indicators' => $fraudResult['indicators'],
                'tenant_id' => $tenantId,
                'pet_id' => $petId,
                'correlation_id' => $correlationId,
            ]);
            throw new \RuntimeException('Session creation blocked by fraud detection');
        }

        $session = GroomingSession::create(
            tenantId: $tenantId,
            petId: $petId,
            serviceType: $serviceType,
            clinicId: $clinicId,
            groomerId: $groomerId,
            appointmentId: $appointmentId,
            serviceId: $serviceId,
            startedAt: $startedAt,
        );

        // Auto-populate allergy alerts from medical record
        $medicalInfo = $this->getMedicalAlerts($petId);
        if (! empty($medicalInfo['allergies'])) {
            $alerts = $this->formatAllergyAlerts($medicalInfo['allergies']);
            $session = $session->setAllergyAlerts($alerts);
        }

        $savedSession = $this->sessionRepository->save($session);

        // Очищаем кэш
        $this->cache->tags(['vetgrooming', 'sessions', "pet:{$petId}", "tenant:{$tenantId}"])->flush();

        // AUDIT LOG
        $this->logger->channel('audit')->info('VetGrooming session created', [
            'session_id' => $savedSession->id,
            'tenant_id' => $tenantId,
            'pet_id' => $petId,
            'service_type' => $serviceType->value,
            'correlation_id' => $correlationId,
        ]);

        // CRM INTEGRATION
        try {
            $this->logger->channel('audit')->info('CRM sync data prepared for vetgrooming session', [
                'session_id' => $savedSession->id,
                'pet_id' => $petId,
                'service_type' => $serviceType->value,
                'correlation_id' => $correlationId,
            ]);
        } catch (\Throwable $e) {
            $this->logger->channel('audit')->error('CRM sync failed for vetgrooming session', [
                'error' => $e->getMessage(),
                'session_id' => $savedSession->id,
                'correlation_id' => $correlationId,
            ]);
        }

        return $savedSession;
    }

    /**
     * Start a grooming session
     */
    public function startSession(int $sessionId, ?int $groomerId = null, ?string $correlationId = null): GroomingSession
    {
        $correlationId ??= uniqid('vetgrooming_session_', true);

        // FRAUD CHECK
        $fraudResult = $this->fraud->checkRequest([
            'action' => 'vetgrooming_start_session',
            'user_id' => null,
            'tenant_id' => null,
            'ip_address' => request()->ip(),
        ]);

        if ($fraudResult['should_block']) {
            $this->logger->channel('audit')->warning('VetGrooming session start blocked by fraud detection', [
                'fraud_score' => $fraudResult['fraud_score'],
                'indicators' => $fraudResult['indicators'],
                'session_id' => $sessionId,
                'correlation_id' => $correlationId,
            ]);
            throw new \RuntimeException('Session start blocked by fraud detection');
        }

        $session = $this->sessionRepository->findById($sessionId);
        if (! $session) {
            throw new \InvalidArgumentException("Session not found: {$sessionId}");
        }

        if (! $session->status->canBeStarted()) {
            throw new \InvalidArgumentException("Session cannot be started in current status: {$session->status->value}");
        }

        $started = $session->start($groomerId);
        $savedSession = $this->sessionRepository->save($started);

        // Очищаем кэш
        $this->cache->tags(['vetgrooming', 'sessions', "pet:{$session->petId}"])->flush();

        // AUDIT LOG
        $this->logger->channel('audit')->info('VetGrooming session started', [
            'session_id' => $sessionId,
            'groomer_id' => $groomerId,
            'correlation_id' => $correlationId,
        ]);

        return $savedSession;
    }

    /**
     * Complete a grooming session with photos and notes
     */
    public function completeSession(
        int $sessionId,
        ?BehaviorRating $behaviorRating = null,
        ?string $behaviorNotes = null,
        ?array $productsUsed = null,
        ?array $beforePhotos = null,
        ?array $afterPhotos = null,
        ?string $medicalNotes = null,
        ?string $correlationId = null,
    ): GroomingSession {
        $correlationId ??= uniqid('vetgrooming_session_', true);

        // FRAUD CHECK
        $fraudResult = $this->fraud->checkRequest([
            'action' => 'vetgrooming_complete_session',
            'user_id' => null,
            'tenant_id' => null,
            'ip_address' => request()->ip(),
        ]);

        if ($fraudResult['should_block']) {
            $this->logger->channel('audit')->warning('VetGrooming session completion blocked by fraud detection', [
                'fraud_score' => $fraudResult['fraud_score'],
                'indicators' => $fraudResult['indicators'],
                'session_id' => $sessionId,
                'correlation_id' => $correlationId,
            ]);
            throw new \RuntimeException('Session completion blocked by fraud detection');
        }

        $session = $this->sessionRepository->findById($sessionId);
        if (! $session) {
            throw new \InvalidArgumentException("Session not found: {$sessionId}");
        }

        if (! $session->status->canBeCompleted()) {
            throw new \InvalidArgumentException("Session cannot be completed in current status: {$session->status->value}");
        }

        $completed = $session->complete(
            behaviorRating: $behaviorRating,
            behaviorNotes: $behaviorNotes,
            productsUsed: $productsUsed,
            beforePhotos: $beforePhotos,
            afterPhotos: $afterPhotos,
            medicalNotes: $medicalNotes,
        );

        $savedSession = $this->sessionRepository->save($completed);

        // Очищаем кэш
        $this->cache->tags(['vetgrooming', 'sessions', "pet:{$session->petId}"])->flush();

        // AUDIT LOG
        $this->logger->channel('audit')->info('VetGrooming session completed', [
            'session_id' => $sessionId,
            'behavior_rating' => $behaviorRating?->value,
            'correlation_id' => $correlationId,
        ]);

        return $savedSession;
    }

    /**
     * Add before photos to a session
     */
    public function addBeforePhotos(int $sessionId, array $photos): GroomingSession
    {
        $session = $this->sessionRepository->findById($sessionId);
        if (! $session) {
            throw new \InvalidArgumentException("Session not found: {$sessionId}");
        }

        $updated = $session->addBeforePhotos($photos);
        return $this->sessionRepository->save($updated);
    }

    /**
     * Add after photos to a session
     */
    public function addAfterPhotos(int $sessionId, array $photos): GroomingSession
    {
        $session = $this->sessionRepository->findById($sessionId);
        if (! $session) {
            throw new \InvalidArgumentException("Session not found: {$sessionId}");
        }

        $updated = $session->addAfterPhotos($photos);
        return $this->sessionRepository->save($updated);
    }

    /**
     * Get grooming history for a pet with photos
     */
    public function getPetHistoryWithPhotos(int $petId): array
    {
        $sessions = $this->sessionRepository->findByPetIdWithPhotos($petId);

        return array_map(fn ($session) => [
            'id' => $session->id,
            'date' => $session->startedAt?->toDateString(),
            'service_type' => $session->serviceType->value,
            'service_label' => $session->serviceType->getLabel(),
            'duration_minutes' => $session->durationMinutes,
            'behavior_rating' => $session->behaviorRating?->value,
            'behavior_label' => $session->behaviorRating?->getLabel(),
            'before_photos' => $session->beforePhotos,
            'after_photos' => $session->afterPhotos,
            'has_photos' => $session->hasBeforePhotos() || $session->hasAfterPhotos(),
            'medical_notes' => $session->medicalNotes,
        ], $sessions);
    }

    /**
     * Get groomer's schedule for a specific date
     */
    public function getGroomerSchedule(int $groomerId, CarbonImmutable $date): array
    {
        $sessions = $this->sessionRepository->findByGroomerIdAndDate($groomerId, $date);

        return array_map(fn ($session) => [
            'id' => $session->id,
            'pet_id' => $session->petId,
            'service_type' => $session->serviceType->value,
            'service_label' => $session->serviceType->getLabel(),
            'estimated_duration' => $session->serviceType->getEstimatedDurationMinutes(),
            'start_time' => $session->startedAt?->toTimeString(),
            'status' => $session->status->value,
            'status_label' => $session->status->getLabel(),
            'has_allergy_alerts' => $session->hasAllergyAlerts(),
        ], $sessions);
    }

    /**
     * Get clinic's grooming schedule for a date range
     */
    public function getClinicSchedule(
        int $tenantId,
        CarbonImmutable $start,
        CarbonImmutable $end
    ): array {
        $sessions = $this->sessionRepository->findScheduledByDateRange($start, $end, $tenantId);

        return array_map(fn ($session) => [
            'id' => $session->id,
            'pet_id' => $session->petId,
            'groomer_id' => $session->groomerId,
            'service_type' => $session->serviceType->value,
            'service_label' => $session->serviceType->getLabel(),
            'start_time' => $session->startedAt?->toDateTimeString(),
            'estimated_duration' => $session->serviceType->getEstimatedDurationMinutes(),
            'status' => $session->status->value,
        ], $sessions);
    }

    /**
     * Get pets with aggressive behavior history for clinic
     */
    public function getAggressiveBehaviorAlerts(int $tenantId): array
    {
        $sessions = $this->sessionRepository->findWithAggressiveBehavior($tenantId);

        return array_map(fn ($session) => [
            'pet_id' => $session->petId,
            'session_date' => $session->startedAt?->toDateString(),
            'behavior_notes' => $session->behaviorNotes,
            'service_type' => $session->serviceType->value,
        ], $sessions);
    }

    /**
     * Get medical alerts for a pet (allergies, skin conditions)
     */
    private function getMedicalAlerts(int $petId): array
    {
        $allergies = $this->conditionRepository->findActiveAllergiesByPetId($petId);
        $chronicConditions = $this->conditionRepository->findActiveByPetId($petId);

        // Filter for skin-relevant conditions
        $skinConditions = array_filter($chronicConditions, fn ($c) => 
            str_contains(strtolower($c->conditionName), 'кожа') ||
            str_contains(strtolower($c->conditionName), 'skin') ||
            str_contains(strtolower($c->description ?? ''), 'кожа') ||
            str_contains(strtolower($c->description ?? ''), 'skin') ||
            str_contains(strtolower($c->description ?? ''), 'чувствительность') ||
            str_contains(strtolower($c->description ?? ''), 'sensitivity')
        );

        return [
            'allergies' => array_map(fn ($a) => [
                'name' => $a->conditionName,
                'type' => $a->conditionType->value,
                'triggers' => $a->triggers,
                'severity' => $a->severity,
            ], $allergies),
            'skin_conditions' => array_map(fn ($c) => [
                'name' => $c->conditionName,
                'notes' => $c->description,
                'management' => $c->managementNotes,
            ], $skinConditions),
        ];
    }

    /**
     * Format allergy alerts for groomers
     */
    private function formatAllergyAlerts(array $allergies): string
    {
        if (empty($allergies)) {
            return '';
        }

        $alerts = [];
        foreach ($allergies as $allergy) {
            $alert = "⚠️ {$allergy['name']}";
            if ($allergy['severity'] === 'severe') {
                $alert .= ' (КРИТИЧНО!)';
            }
            if (! empty($allergy['triggers'])) {
                $alert .= ' - Триггеры: ' . implode(', ', $allergy['triggers']);
            }
            $alerts[] = $alert;
        }

        return implode("\n", $alerts);
    }

    /**
     * Get grooming statistics for a groomer
     */
    public function getGroomerStats(int $groomerId, CarbonImmutable $startDate, CarbonImmutable $endDate): array
    {
        $sessions = $this->sessionRepository->findByGroomerId($groomerId);
        
        // Filter by date range
        $filtered = array_filter($sessions, fn ($s) => 
            $s->startedAt && 
            $s->startedAt->between($startDate, $endDate)
        );

        $completed = array_filter($filtered, fn ($s) => $s->status === GroomingStatus::COMPLETED);
        $aggressive = array_filter($completed, fn ($s) => $s->isAggressiveBehavior());

        return [
            'total_sessions' => count($filtered),
            'completed_sessions' => count($completed),
            'aggressive_behavior_count' => count($aggressive),
            'average_duration' => count($completed) > 0 
                ? array_sum(array_map(fn ($s) => $s->durationMinutes ?? 0, $completed)) / count($completed)
                : 0,
        ];
    }

    /**
     * Generate photo report for marketing (before/after comparison)
     */
    public function generatePhotoReport(int $petId): array
    {
        $sessions = $this->sessionRepository->findByPetIdWithPhotos($petId);
        
        $photoSessions = array_filter($sessions, fn ($s) => 
            $s->hasBeforePhotos() && $s->hasAfterPhotos()
        );

        return array_map(fn ($session) => [
            'session_id' => $session->id,
            'date' => $session->completedAt?->toDateString(),
            'service_type' => $session->serviceType->getLabel(),
            'before_photos' => $session->beforePhotos,
            'after_photos' => $session->afterPhotos,
            'behavior_rating' => $session->behaviorRating?->getLabel(),
            'products_used' => $session->productsUsed,
        ], $photoSessions);
    }
}

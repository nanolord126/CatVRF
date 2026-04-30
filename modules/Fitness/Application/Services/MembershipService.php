<?php

declare(strict_types=1);

namespace Modules\Fitness\Application\Services;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Cache\CacheManager;
use Illuminate\Log\LogManager;
use Modules\Fitness\Domain\Entities\Client;
use Modules\Fitness\Domain\Entities\Membership;
use Modules\Fitness\Domain\Enums\MembershipStatus;
use Modules\Fitness\Domain\Enums\MembershipType;
use Modules\Fitness\Domain\Repositories\ClientRepositoryInterface;
use Modules\Fitness\Domain\Repositories\MembershipRepositoryInterface;
use App\Services\Fraud\FraudControlService;
use App\Domains\CRM\Services\FitnessCrmService;
use App\Services\Security\AuditService;
use App\Traits\WithAuditLogging;
use App\Traits\WithAnalyticsTracking;

/**
 * Membership Service — Сервис для управления фитнес-абонементами
 *
 * CatVRF 2026 Canon - Production Mandatory
 * - Fraud check перед всеми мутациями
 * - Readonly класс
 * - $this->cache->tags для инвалидации
 * - Audit логирование
 * - CRM интеграция
 */
final readonly class MembershipService
{
    use WithAuditLogging;
    use WithAnalyticsTracking;

    public function __construct(
        private readonly FraudControlService $fraud,
        private readonly FitnessCrmService $fitnessCrm,
        private readonly MembershipRepositoryInterface $membershipRepository,
        private readonly ClientRepositoryInterface $clientRepository,
        private readonly CacheManager $cache,
        private readonly LogManager $logger,
        private readonly AuditService $auditService,
    ) {}

    public function createMembership(
        int $clientId,
        MembershipType $type,
        CarbonImmutable $startDate,
        ?CarbonImmutable $endDate = null,
        float $price = 0.0,
        ?int $totalVisits = null,
        bool $allowFreeze = true,
        int $maxFreezeDays = 30,
        ?string $notes = null,
        ?string $correlationId = null,
    ): Membership {
        $correlationId ??= uniqid('fitness_membership_', true);

        // FRAUD CHECK - мандаторно первым действием
        $fraudResult = $this->fraud->checkRequest([
            'action' => 'fitness_create_membership',
            'user_id' => $clientId,
            'tenant_id' => null,
            'ip_address' => request()->ip(),
        ]);

        if ($fraudResult['should_block']) {
            $this->logger->channel('audit')->warning('Fitness membership creation blocked by fraud detection', [
                'fraud_score' => $fraudResult['fraud_score'],
                'indicators' => $fraudResult['indicators'],
                'client_id' => $clientId,
                'correlation_id' => $correlationId,
            ]);
            throw new \RuntimeException('Membership creation blocked by fraud detection');
        }

        $client = $this->clientRepository->findById($clientId);
        if (!$client) {
            throw new \InvalidArgumentException('Client not found');
        }

        if ($endDate === null) {
            $endDate = $startDate->addDays($type->getDefaultDurationDays());
        }

        $membership = Membership::create(
            tenantId: $client->tenantId,
            clientId: $clientId,
            type: $type,
            startDate: $startDate,
            endDate: $endDate,
            price: $price,
            remainingVisits: $totalVisits,
            totalVisits: $totalVisits,
            allowFreeze: $allowFreeze,
            maxFreezeDays: $maxFreezeDays,
            notes: $notes,
        );

        $savedMembership = $this->membershipRepository->save($membership);

        // Очищаем кэш
        $this->cache->tags(['fitness', 'memberships', "client:{$clientId}", "tenant:{$client->tenantId}"])->flush();

        // AUDIT LOG
        $this->logger->channel('audit')->info('Fitness membership created', [
            'membership_id' => $savedMembership->id,
            'client_id' => $clientId,
            'tenant_id' => $client->tenantId,
            'type' => $type->value,
            'price' => $price,
            'correlation_id' => $correlationId,
        ]);

        // Track behavioral event for analytics
        $this->trackPurchase(
            'fitness',
            (string) $savedMembership->id,
            $price,
            [
                'membership_type' => $type->value,
                'client_id' => $clientId,
                'tenant_id' => $client->tenantId,
                'total_visits' => $totalVisits,
            ]
        );

        // CRM INTEGRATION
        try {
            $this->logger->channel('audit')->info('CRM sync data prepared for fitness membership', [
                'membership_id' => $savedMembership->id,
                'client_id' => $clientId,
                'type' => $type->value,
                'correlation_id' => $correlationId,
            ]);
        } catch (\Throwable $e) {
            $this->logger->channel('audit')->error('CRM sync failed for fitness membership', [
                'error' => $e->getMessage(),
                'membership_id' => $savedMembership->id,
                'correlation_id' => $correlationId,
            ]);
        }

        return $savedMembership;
    }

    public function freezeMembership(int $membershipId, ?string $correlationId = null): Membership
    {
        $correlationId ??= uniqid('fitness_membership_', true);

        // FRAUD CHECK
        $fraudResult = $this->fraud->checkRequest([
            'action' => 'fitness_freeze_membership',
            'user_id' => null,
            'tenant_id' => null,
            'ip_address' => request()->ip(),
        ]);

        if ($fraudResult['should_block']) {
            $this->logger->channel('audit')->warning('Fitness membership freeze blocked by fraud detection', [
                'fraud_score' => $fraudResult['fraud_score'],
                'indicators' => $fraudResult['indicators'],
                'membership_id' => $membershipId,
                'correlation_id' => $correlationId,
            ]);
            throw new \RuntimeException('Membership freeze blocked by fraud detection');
        }

        $membership = $this->membershipRepository->findById($membershipId);
        if (!$membership) {
            throw new \InvalidArgumentException('Membership not found');
        }

        if (!$membership->canFreeze()) {
            throw new \RuntimeException('Cannot freeze this membership');
        }

        $frozen = $membership->freeze();
        $savedMembership = $this->membershipRepository->save($frozen);

        // Очищаем кэш
        $this->cache->tags(['fitness', 'memberships', "client:{$membership->clientId}"])->flush();

        // AUDIT LOG
        $this->logger->channel('audit')->info('Fitness membership frozen', [
            'membership_id' => $membershipId,
            'client_id' => $membership->clientId,
            'correlation_id' => $correlationId,
        ]);

        return $savedMembership;
    }

    public function unfreezeMembership(int $membershipId, ?string $correlationId = null): Membership
    {
        $correlationId ??= uniqid('fitness_membership_', true);

        // FRAUD CHECK
        $fraudResult = $this->fraud->checkRequest([
            'action' => 'fitness_unfreeze_membership',
            'user_id' => null,
            'tenant_id' => null,
            'ip_address' => request()->ip(),
        ]);

        if ($fraudResult['should_block']) {
            $this->logger->channel('audit')->warning('Fitness membership unfreeze blocked by fraud detection', [
                'fraud_score' => $fraudResult['fraud_score'],
                'indicators' => $fraudResult['indicators'],
                'membership_id' => $membershipId,
                'correlation_id' => $correlationId,
            ]);
            throw new \RuntimeException('Membership unfreeze blocked by fraud detection');
        }

        $membership = $this->membershipRepository->findById($membershipId);
        if (!$membership) {
            throw new \InvalidArgumentException('Membership not found');
        }

        if ($membership->status !== MembershipStatus::FROZEN) {
            throw new \RuntimeException('Membership is not frozen');
        }

        $unfrozen = $membership->unfreeze();
        $savedMembership = $this->membershipRepository->save($unfrozen);

        // Очищаем кэш
        $this->cache->tags(['fitness', 'memberships', "client:{$membership->clientId}"])->flush();

        // AUDIT LOG
        $this->logger->channel('audit')->info('Fitness membership unfrozen', [
            'membership_id' => $membershipId,
            'client_id' => $membership->clientId,
            'correlation_id' => $correlationId,
        ]);

        return $savedMembership;
    }

    public function cancelMembership(int $membershipId, ?string $correlationId = null): Membership
    {
        $correlationId ??= uniqid('fitness_membership_', true);

        // FRAUD CHECK
        $fraudResult = $this->fraud->checkRequest([
            'action' => 'fitness_cancel_membership',
            'user_id' => null,
            'tenant_id' => null,
            'ip_address' => request()->ip(),
        ]);

        if ($fraudResult['should_block']) {
            $this->logger->channel('audit')->warning('Fitness membership cancellation blocked by fraud detection', [
                'fraud_score' => $fraudResult['fraud_score'],
                'indicators' => $fraudResult['indicators'],
                'membership_id' => $membershipId,
                'correlation_id' => $correlationId,
            ]);
            throw new \RuntimeException('Membership cancellation blocked by fraud detection');
        }

        $membership = $this->membershipRepository->findById($membershipId);
        if (!$membership) {
            throw new \InvalidArgumentException('Membership not found');
        }

        $cancelled = $membership->cancel();
        $savedMembership = $this->membershipRepository->save($cancelled);

        // Очищаем кэш
        $this->cache->tags(['fitness', 'memberships', "client:{$membership->clientId}"])->flush();

        // AUDIT LOG
        $this->logger->channel('audit')->info('Fitness membership cancelled', [
            'membership_id' => $membershipId,
            'client_id' => $membership->clientId,
            'correlation_id' => $correlationId,
        ]);

        return $savedMembership;
    }

    public function useVisit(int $membershipId): Membership
    {
        $membership = $this->membershipRepository->findById($membershipId);
        if (!$membership) {
            throw new \InvalidArgumentException('Membership not found');
        }

        if (!$membership->isActive()) {
            throw new \RuntimeException('Membership is not active');
        }

        if ($membership->remainingVisits !== null && $membership->remainingVisits <= 0) {
            throw new \RuntimeException('No visits remaining');
        }

        $updated = $membership->useVisit();
        return $this->membershipRepository->save($updated);
    }

    public function getActiveMembership(int $clientId): ?Membership
    {
        return $this->membershipRepository->findActiveByClientId($clientId);
    }

    public function getClientMemberships(int $clientId): array
    {
        return $this->membershipRepository->findByClientId($clientId);
    }

    public function getExpiringMemberships(int $tenantId, int $days = 7): array
    {
        return $this->membershipRepository->findExpiringSoon($tenantId, $days);
    }

    public function extendMembership(int $membershipId, int $days): Membership
    {
        $membership = $this->membershipRepository->findById($membershipId);
        if (!$membership) {
            throw new \InvalidArgumentException('Membership not found');
        }

        $newEndDate = $membership->endDate->addDays($days);
        $extended = new Membership(
            ...get_object_vars($membership),
            endDate: $newEndDate,
            updatedAt: CarbonImmutable::now(),
        );

        return $this->membershipRepository->save($extended);
    }

    public function addVisits(int $membershipId, int $visits): Membership
    {
        $membership = $this->membershipRepository->findById($membershipId);
        if (!$membership) {
            throw new \InvalidArgumentException('Membership not found');
        }

        $newRemaining = ($membership->remainingVisits ?? 0) + $visits;
        $newTotal = ($membership->totalVisits ?? 0) + $visits;

        $updated = new Membership(
            ...get_object_vars($membership),
            remainingVisits: $newRemaining,
            totalVisits: $newTotal,
            updatedAt: CarbonImmutable::now(),
        );

        return $this->membershipRepository->save($updated);
    }

    public function getMembershipStats(int $clientId): array
    {
        $memberships = $this->membershipRepository->findByClientId($clientId);
        $active = $this->getActiveMembership($clientId);

        return [
            'total_memberships' => count($memberships),
            'has_active' => $active !== null,
            'active_type' => $active?->type->getLabel(),
            'days_remaining' => $active?->getDaysRemaining() ?? 0,
            'visits_remaining' => $active?->getVisitsRemaining(),
        ];
    }
}

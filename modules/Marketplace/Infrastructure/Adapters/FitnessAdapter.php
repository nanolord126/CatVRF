<?php

declare(strict_types=1);

namespace Modules\Marketplace\Infrastructure\Adapters;

use Modules\Fitness\Domain\Entities\Membership;
use Modules\Fitness\Domain\Repositories\MembershipRepositoryInterface;
use Modules\Marketplace\Domain\ValueObjects\VerticalSource;
use Psr\Log\LoggerInterface;

final class FitnessAdapter implements VerticalAdapterInterface
{
    public function __construct(
        private readonly MembershipRepositoryInterface $membershipRepository,
        private readonly LoggerInterface $logger,
    ) {}

    public function fetchSources(array $filters = [], int $limit = 100): array
    {
        $this->logger->info('Fetching Fitness memberships', ['filters' => $filters, 'limit' => $limit]);

        $memberships = $this->membershipRepository->findActive($limit);

        $sources = [];
        foreach ($memberships as $membership) {
            try {
                $sources[] = $this->convertMembershipToArray($membership);
            } catch (\Throwable $e) {
                $this->logger->error('Failed to convert membership', [
                    'membership_id' => $membership->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $sources;
    }

    public function fetchSingle(int $sourceId, array $filters = []): ?array
    {
        $membership = $this->membershipRepository->findById($sourceId);
        if ($membership === null) {
            return null;
        }

        return $this->convertMembershipToArray($membership);
    }

    public function countSources(array $filters = []): int
    {
        return $this->membershipRepository->countActive();
    }

    public function validateSource(array $source): bool
    {
        $required = ['id', 'title', 'price', 'category'];
        foreach ($required as $field) {
            if (!isset($source[$field])) {
                return false;
            }
        }

        return !empty($source['title']) && $source['price'] > 0;
    }

    public function getSupportedEntityTypes(): array
    {
        return ['subscription', 'membership'];
    }

    public function getDefaultFilters(): array
    {
        return [
            'status' => 'active',
            'is_available' => true,
            'auto_publish' => true,
        ];
    }

    private function convertMembershipToArray(Membership $membership): array
    {
        return [
            'id' => $membership->id,
            'title' => $membership->name,
            'description' => $membership->description ?? '',
            'price' => $membership->price,
            'currency' => 'RUB',
            'category' => $this->mapCategory($membership->type),
            'type' => 'subscription',
            'tags' => array_merge(
                $membership->features ?? [],
                $membership->isUnlimited ? ['unlimited'] : [],
                $membership->includesGroupClasses ? ['group_classes'] : [],
            ),
            'images' => $membership->images ?? [],
            'attributes' => [
                'gym_id' => $membership->gymId,
                'duration_days' => $membership->durationDays,
                'visits_limit' => $membership->visitsLimit,
                'includes_personal_training' => $membership->includesPersonalTraining,
            ],
            'metadata' => [
                'vertical' => VerticalSource::FITNESS->value,
                'source_type' => 'membership',
                'supports_booking' => true,
            ],
            'tenant_id' => $membership->tenantId,
            'business_group_id' => $membership->businessGroupId,
        ];
    }

    private function mapCategory(?string $category): string
    {
        $mapping = [
            'monthly' => 'monthly',
            'quarterly' => 'quarterly',
            'annual' => 'annual',
            'day_pass' => 'day_pass',
            'personal_training' => 'personal_training',
        ];

        return $mapping[$category ?? ''] ?? 'fitness';
    }
}

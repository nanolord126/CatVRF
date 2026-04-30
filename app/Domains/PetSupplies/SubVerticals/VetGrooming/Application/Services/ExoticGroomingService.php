<?php

declare(strict_types=1);

namespace Modules\VetGrooming\Application\Services;

use App\Traits\WithAuditLogging;
use App\Services\Security\AuditService;
use Modules\VetGrooming\Domain\Entities\ExoticCertification;
use Modules\VetGrooming\Domain\Entities\ExoticGroomingSession;
use Modules\VetGrooming\Domain\Entities\SmallMammalGroomingSession;
use Modules\VetGrooming\Domain\Entities\LargeMammalGroomingSession;
use Modules\VetGrooming\Domain\Entities\ExoticSafetyProtocol;
use Modules\VetGrooming\Domain\Repositories\ExoticCertificationRepositoryInterface;
use Modules\VetGrooming\Domain\Repositories\ExoticGroomingSessionRepositoryInterface;
use Modules\VetGrooming\Domain\Repositories\SmallMammalGroomingSessionRepositoryInterface;
use Modules\VetGrooming\Domain\Repositories\LargeMammalGroomingSessionRepositoryInterface;
use Modules\VetGrooming\Domain\Repositories\ExoticSafetyProtocolRepositoryInterface;
use Modules\VetGrooming\Domain\Enums\ExoticCategory;
use Modules\VetGrooming\Domain\Enums\ExoticGroup;
use Modules\VetGrooming\Domain\Enums\CertificationLevel;
use Modules\VetGrooming\Domain\Enums\ExoticProcedureType;
use Modules\VetGrooming\Domain\Enums\HandlingMethod;
use Modules\VetGrooming\Domain\Exceptions\InsufficientCertificationException;
use Modules\VetGrooming\Domain\Exceptions\CertificationExpiredException;
use Modules\VetGrooming\Domain\Exceptions\SafetyProtocolViolationException;
use Illuminate\Cache\CacheManager;
use Illuminate\Log\LogManager;

/**
 * ExoticGroomingService — Сервис для груминга экзотических животных
 *
 * CatVRF 2026 Canon - Production Mandatory
 * - Dependency injection instead of facades
 * - Readonly class
 * - Audit logging
 */
final readonly class ExoticGroomingService
{
    use WithAuditLogging;

    public function __construct(
        private ExoticCertificationRepositoryInterface $certificationRepository,
        private ExoticGroomingSessionRepositoryInterface $exoticSessionRepository,
        private SmallMammalGroomingSessionRepositoryInterface $smallMammalSessionRepository,
        private LargeMammalGroomingSessionRepositoryInterface $largeMammalSessionRepository,
        private ExoticSafetyProtocolRepositoryInterface $protocolRepository,
        private readonly AuditService $audit,
        private readonly CacheManager $cache,
        private readonly LogManager $log,
    ) {}

    /**
     * Validate groomer certification for exotic animal grooming
     *
     * @throws InsufficientCertificationException
     * @throws CertificationExpiredException
     */
    public function validateGroomerCertification(
        int $groomerId,
        ExoticCategory $category,
        string $group,
        ?string $species = null,
    ): ExoticCertification {
        $cacheKey = "groomer_certification:{$groomerId}:{$category->value}:{$group}";

        $certification = $this->cache->remember($cacheKey, now()->addMinutes(15), function () use ($groomerId, $category, $group) {
            return $this->certificationRepository->findValidByMasterAndGroup(
                $groomerId,
                ExoticGroup::from($group)
            );
        });

        if ($certification === null) {
            $this->logCertificationFailure($groomerId, $category, $group);
            throw new InsufficientCertificationException(
                sprintf(
                    'Groomer %d does not have valid certification for %s (%s). Required level: %s',
                    $groomerId,
                    $category->getLabel(),
                    $group,
                    ExoticGroup::from($group)->getRequiredCertificationLevel()->getLabel()
                )
            );
        }

        if (!$certification->isValid()) {
            if ($certification->isExpired()) {
                throw new CertificationExpiredException(
                    sprintf('Certification for groomer %d expired on %s', $groomerId, $certification->expiryDate->format('Y-m-d'))
                );
            }

            throw new InsufficientCertificationException(
                sprintf('Certification for groomer %d is not active (status: %s)', $groomerId, $certification->status)
            );
        }

        return $certification;
    }

    /**
     * Check if groomer can work with specific exotic group
     */
    public function canGroomerWorkWithGroup(int $groomerId, ExoticGroup $group): bool
    {
        $certification = $this->certificationRepository->findValidByMasterAndGroup($groomerId, $group);
        
        if ($certification === null) {
            return false;
        }

        return $certification->isValid() && $certification->certificationLevel->canWorkAloneWithGroup($group->value);
    }

    /**
     * Get recommended groomers for exotic animal
     *
     * @return array<int, array{id: int, name: string, level: string}>
     */
    public function getRecommendedGroomersForExotic(
        ExoticCategory $category,
        ExoticGroup $group,
        int $tenantId,
        int $limit = 10,
    ): array {
        $certifications = $this->certificationRepository->findByCategory($category, $tenantId, $limit);

        $recommended = [];
        foreach ($certifications as $cert) {
            if ($cert->isValid() && $cert->certificationLevel->canWorkWithGroup($group->value)) {
                $recommended[] = [
                    'id' => $cert->masterId,
                    'level' => $cert->certificationLevel->getLabel(),
                    'certificate_number' => $cert->certificateNumber,
                ];
            }
        }

        return array_slice($recommended, 0, $limit);
    }

    /**
     * Create exotic grooming session (birds/reptiles)
     *
     * @throws InsufficientCertificationException
     * @throws CertificationExpiredException
     */
    public function createExoticGroomingSession(
        int $petId,
        int $groomerId,
        int $tenantId,
        ExoticCategory $exoticType,
        string $speciesGroup,
        ExoticProcedureType $procedureType,
        int $stressLevelBefore,
        HandlingMethod $handlingMethod,
        ?int $appointmentId = null,
    ): ExoticGroomingSession {
        // Validate certification
        $this->validateGroomerCertification($groomerId, $exoticType, match ($exoticType) {
            ExoticCategory::BIRDS => 'group_a', // Birds are high risk
            ExoticCategory::REPTILES => 'group_a', // Reptiles are high risk
            default => 'group_c',
        });

        $session = ExoticGroomingSession::create(
            $petId,
            $groomerId,
            $tenantId,
            $exoticType,
            $speciesGroup,
            $procedureType,
            $stressLevelBefore,
            $handlingMethod,
            $appointmentId,
        );

        return $this->exoticSessionRepository->save($session);
    }

    /**
     * Create small mammal grooming session
     *
     * @throws InsufficientCertificationException
     * @throws CertificationExpiredException
     */
    public function createSmallMammalGroomingSession(
        int $petId,
        int $groomerId,
        int $tenantId,
        string $mammalGroup,
        ExoticProcedureType $procedureType,
        int $stressLevelBefore,
        HandlingMethod $handlingMethod,
        ?int $appointmentId = null,
    ): SmallMammalGroomingSession {
        // Determine required group based on mammal type
        $requiredGroup = match ($mammalGroup) {
            'ferret', 'rabbit', 'chinchilla' => 'group_a', // High risk
            'guinea_pig', 'degu', 'hedgehog' => 'group_b', // Medium risk
            'rodents' => 'group_c', // Lower risk
            default => 'group_c',
        };

        $this->validateGroomerCertification(
            $groomerId,
            ExoticCategory::SMALL_MAMMALS,
            $requiredGroup,
            $mammalGroup
        );

        $session = SmallMammalGroomingSession::create(
            $petId,
            $groomerId,
            $tenantId,
            $mammalGroup,
            $procedureType,
            $stressLevelBefore,
            $handlingMethod,
            $appointmentId,
        );

        return $this->smallMammalSessionRepository->save($session);
    }

    /**
     * Create large mammal grooming session
     *
     * @throws InsufficientCertificationException
     * @throws CertificationExpiredException
     * @throws SafetyProtocolViolationException
     */
    public function createLargeMammalGroomingSession(
        int $petId,
        int $groomerId,
        int $tenantId,
        string $mammalGroup,
        ExoticProcedureType $procedureType,
        int $aggressionLevel,
        int $stressLevelBefore,
        HandlingMethod $restraintMethod,
        ?int $appointmentId = null,
    ): LargeMammalGroomingSession {
        // Determine required group based on mammal type
        $requiredGroup = match ($mammalGroup) {
            'giant_dog' => 'group_a', // Highest risk
            'large_dog', 'large_cat' => 'group_a', // High risk
            'other_large' => 'group_b', // Medium risk
            default => 'group_b',
        };

        $certification = $this->validateGroomerCertification(
            $groomerId,
            ExoticCategory::LARGE_MAMMALS,
            $requiredGroup,
            $mammalGroup
        );

        // Check safety rules for high aggression
        if ($aggressionLevel >= 6 && $certification->certificationLevel !== CertificationLevel::MASTER) {
            throw new SafetyProtocolViolationException(
                'Aggression level ≥ 6 requires Master certification and second groomer'
            );
        }

        $session = LargeMammalGroomingSession::create(
            $petId,
            $groomerId,
            $tenantId,
            $mammalGroup,
            $procedureType,
            $aggressionLevel,
            $stressLevelBefore,
            $restraintMethod,
            $appointmentId,
        );

        return $this->largeMammalSessionRepository->save($session);
    }

    /**
     * Get safety protocol for species
     */
    public function getSafetyProtocol(
        ExoticCategory $category,
        string $species,
        int $tenantId,
    ): ?ExoticSafetyProtocol {
        $cacheKey = "safety_protocol:{$category->value}:{$species}:{$tenantId}";

        return $this->cache->remember($cacheKey, now()->addHours(1), function () use ($category, $species, $tenantId) {
            return $this->protocolRepository->findByCategoryAndSpecies($category, $species, $tenantId);
        });
    }

    /**
     * Check if protocol checklist is complete
     *
     * @throws SafetyProtocolViolationException
     */
    public function validateProtocolChecklist(array $checklist, array $requiredItems): void
    {
        foreach ($requiredItems as $item) {
            if (!isset($checklist[$item]) || !$checklist[$item]) {
                throw new SafetyProtocolViolationException(
                    sprintf('Required protocol item not completed: %s', $item)
                );
            }
        }
    }

    /**
     * Log certification failure for audit
     */
    private function logCertificationFailure(int $groomerId, ExoticCategory $category, string $group): void
    {
        $this->logAction('exotic_grooming_certification_failure', 'ExoticCertification', null, [
            'groomer_id' => $groomerId,
            'category' => $category->value,
            'group' => $group,
            'timestamp' => now()->toIso8601String(),
            'event' => 'certification_validation_failed',
        ], $groomerId, null);
    }

    /**
     * Get groomer's exotic certifications summary
     *
     * @return array<string, mixed>
     */
    public function getGroomerCertificationsSummary(int $groomerId): array
    {
        $certifications = $this->certificationRepository->findByMasterId($groomerId);

        $summary = [
            'groomer_id' => $groomerId,
            'total_certifications' => count($certifications),
            'active_certifications' => 0,
            'expired_certifications' => 0,
            'categories' => [],
            'highest_level' => 'none',
        ];

        $highestHierarchy = 0;

        foreach ($certifications as $cert) {
            if ($cert->isValid()) {
                $summary['active_certifications']++;
                $categoryKey = $cert->exoticCategory->value;
                
                if (!isset($summary['categories'][$categoryKey])) {
                    $summary['categories'][$categoryKey] = [
                        'level' => $cert->certificationLevel->value,
                        'group' => $cert->exoticGroup->value,
                        'expires_at' => $cert->expiryDate->format('Y-m-d'),
                    ];
                }

                if ($cert->certificationLevel->getHierarchy() > $highestHierarchy) {
                    $highestHierarchy = $cert->certificationLevel->getHierarchy();
                    $summary['highest_level'] = $cert->certificationLevel->value;
                }
            } else {
                $summary['expired_certifications']++;
            }
        }

        return $summary;
    }
}

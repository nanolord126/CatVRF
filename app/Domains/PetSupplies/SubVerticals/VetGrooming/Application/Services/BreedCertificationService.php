<?php

declare(strict_types=1);

namespace Modules\VetGrooming\Application\Services;

use App\Traits\WithAuditLogging;
use App\Services\Security\AuditService;
use Modules\VetGrooming\Domain\Entities\BreedCertification;
use Modules\VetGrooming\Domain\Entities\BreedSpecialization;
use Modules\VetGrooming\Domain\Entities\BreedDevelopmentPlan;
use Modules\VetGrooming\Domain\Events\BreedCertificationExpired;
use Modules\VetGrooming\Domain\Repositories\BreedCertificationRepositoryInterface;
use Modules\VetGrooming\Domain\Repositories\BreedSpecializationRepositoryInterface;
use Modules\VetGrooming\Domain\Repositories\BreedDevelopmentPlanRepositoryInterface;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Event;
use Illuminate\Cache\CacheManager;
use Illuminate\Log\LogManager;

/**
 * BreedCertificationService — Сервис для сертификации пород
 *
 * CatVRF 2026 Canon - Production Mandatory
 * - Dependency injection instead of facades
 * - Readonly class
 * - Audit logging
 */
final readonly class BreedCertificationService
{
    use WithAuditLogging;

    // Breed group mappings for common pet breeds
    private const BREED_GROUPS = [
        'dogs' => [
            'terriers' => ['yorkshire_terrier', 'jack_russell', 'fox_terrier', 'bedlington_terrier', 'west_highland_white_terrier'],
            'spitz' => ['pomeranian', 'samoyed', 'husky', 'malamute', 'chow_chow', 'akita'],
            'poodles' => ['poodle', 'bichon_frisee', 'lagotto_romagnolo'],
            'brachycephalic' => ['pug', 'french_bulldog', 'english_bulldog', 'boston_terrier', 'pekingese'],
            'long_haired' => ['maltese', 'shih_tzu', 'afghan_hound', 'yorkshire_terrier', 'lhasa_apso'],
            'wire_haired' => ['schnauzer', 'airedale_terrier', 'fox_terrier_wire', 'scottish_terrier'],
            'short_haired' => ['boxer', 'doberman', 'labrador', 'staffordshire_bull_terrier', 'great_dane'],
            'shepherds' => ['german_shepherd', 'belgian_shepherd', 'rottweiler', 'border_collie'],
        ],
        'cats' => [
            'long_haired' => ['maine_coon', 'persian', 'siberian', 'norwegian_forest_cat', 'ragdoll'],
            'short_haired' => ['british_shorthair', 'scottish_fold', 'abyssinian', 'siamese', 'bengal'],
            'hairless' => ['sphynx', 'peterbald', 'don_sphynx', 'elf_cat'],
        ],
    ];

    // Required certification levels for specific breed groups
    private const REQUIRED_LEVELS = [
        'brachycephalic' => 'advanced', // Higher risk, requires advanced certification
        'terriers' => 'certified',
        'spitz' => 'certified',
        'long_haired' => 'intermediate',
        'hairless' => 'advanced', // Special care required
    ];

    public function __construct(
        private readonly BreedCertificationRepositoryInterface $certificationRepository,
        private readonly BreedSpecializationRepositoryInterface $specializationRepository,
        private readonly BreedDevelopmentPlanRepositoryInterface $developmentPlanRepository,
        private readonly AuditService $audit,
        private readonly CacheManager $cache,
        private readonly LogManager $log,
    ) {}

    /**
     * Check if a groomer has certification for a specific breed.
     */
    public function hasCertificationForBreed(int $masterId, string $petSpecies, string $petBreed, ?string $requiredLevel = null): bool
    {
        $breedGroup = $this->getBreedGroup($petSpecies, $petBreed);
        if ($breedGroup === null) {
            // Unknown breed - return true (no restriction) or handle as needed
            return true;
        }

        $requiredCertificationLevel = $requiredLevel ?? self::REQUIRED_LEVELS[$breedGroup] ?? 'basic';

        // First check for specific breed certification
        $specificCertification = $this->certificationRepository->findByMasterIdAndBreed($masterId, $petBreed);
        if ($specificCertification !== null && $specificCertification->isActive() && $specificCertification->isAtLeastLevel($requiredCertificationLevel)) {
            return true;
        }

        // Then check for breed group certification
        $groupCertifications = $this->certificationRepository->findByMasterIdAndBreedGroup($masterId, $breedGroup);
        foreach ($groupCertifications as $certification) {
            if ($certification->isActive() && $certification->isAtLeastLevel($requiredCertificationLevel)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get available groomers for a specific breed with required level.
     */
    public function getAvailableGroomersForBreed(string $petSpecies, string $petBreed, string $requiredLevel, int $tenantId): array
    {
        $breedGroup = $this->getBreedGroup($petSpecies, $petBreed);
        if ($breedGroup === null) {
            return [];
        }

        $cacheKey = "available_groomers:{$tenantId}:{$breedGroup}:{$requiredLevel}";

        return $this->cache->remember($cacheKey, now()->addMinutes(15), function () use ($breedGroup, $requiredLevel, $tenantId) {
            $certifications = $this->certificationRepository->findByBreedGroup($breedGroup, $requiredLevel, $tenantId);

            $groomerIds = array_unique(array_map(fn ($c) => $c->masterId, $certifications));

            return $groomerIds;
        });
    }

    /**
     * Verify and create a breed certification.
     */
    public function verifyBreedCertification(
        int $masterId,
        string $certificationType,
        string $certificationLevel,
        int $tenantId,
        ?string $breedGroup = null,
        ?string $breedId = null,
        ?float $practicalScore = null,
        ?float $theoryScore = null,
        ?string $examFeedback = null,
        ?string $externalSchoolName = null,
    ): BreedCertification {
        $issuer = $externalSchoolName !== null ? 'external_school' : 'catcrm_internal';

        $certification = BreedCertification::create(
            tenantId: $tenantId,
            masterId: $masterId,
            certificationType: $certificationType,
            certificationLevel: $certificationLevel,
            breedGroup: $breedGroup,
            breedId: $breedId,
            issuer: $issuer,
            externalSchoolName: $externalSchoolName,
        );

        if ($practicalScore !== null || $theoryScore !== null) {
            $certification = $certification->addExamScores($practicalScore, $theoryScore, $examFeedback);
        }

        $savedCertification = $this->certificationRepository->save($certification);

        // Update or create breed specialization
        if ($breedGroup !== null) {
            $this->updateBreedSpecialization($masterId, $breedGroup, $certificationLevel, $tenantId);
        }

        return $savedCertification;
    }

    /**
     * Block booking if groomer doesn't have required certification.
     */
    public function blockBookingIfNoCertification(int $masterId, string $petSpecies, string $petBreed): array
    {
        $breedGroup = $this->getBreedGroup($petSpecies, $petBreed);
        $requiredLevel = $breedGroup !== null ? (self::REQUIRED_LEVELS[$breedGroup] ?? 'basic') : null;

        $hasCertification = $this->hasCertificationForBreed($masterId, $petSpecies, $petBreed, $requiredLevel);

        if (! $hasCertification) {
            $alternativeGroomers = $this->getAvailableGroomersForBreed($petSpecies, $petBreed, $requiredLevel ?? 'basic', 0); // tenant_id should be passed

            return [
                'blocked' => true,
                'reason' => 'Groomer lacks required breed certification',
                'breed_group' => $breedGroup,
                'required_level' => $requiredLevel,
                'alternative_groomers' => $alternativeGroomers,
                'message' => $this->getBlockingMessage($breedGroup, $requiredLevel),
            ];
        }

        // Check if groomer only has basic level but breed requires higher
        if ($requiredLevel !== null && $requiredLevel !== 'basic') {
            $certificationLevel = $this->getHighestCertificationLevel($masterId, $petSpecies, $petBreed);
            if ($certificationLevel !== null && $certificationLevel === 'basic') {
                return [
                    'blocked' => false,
                    'warning' => true,
                    'reason' => 'Groomer has basic certification only',
                    'breed_group' => $breedGroup,
                    'current_level' => $certificationLevel,
                    'required_level' => $requiredLevel,
                    'message' => "Groomer has basic certification for {$breedGroup}, but this breed requires {$requiredLevel} level. Consider redirecting to a more certified groomer.",
                ];
            }
        }

        return [
            'blocked' => false,
            'certified' => true,
        ];
    }

    /**
     * Create a breed development plan for a groomer.
     */
    public function createBreedDevelopmentPlan(
        int $masterId,
        string $targetBreedGroup,
        string $targetLevel,
        int $tenantId,
        ?string $targetBreed = null,
        ?int $mentorId = null,
    ): BreedDevelopmentPlan {
        $plan = BreedDevelopmentPlan::create(
            tenantId: $tenantId,
            masterId: $masterId,
            targetBreedGroup: $targetBreedGroup,
            targetLevel: $targetLevel,
            targetBreed: $targetBreed,
            mentorId: $mentorId,
        );

        // Set requirements based on target level
        $requirements = $this->getRequirementsForLevel($targetLevel);
        $plan = $plan->setRequirements($requirements['training_hours'], $requirements['practical_hours']);

        // Set training modules
        $trainingModules = $this->getTrainingModulesForBreedGroup($targetBreedGroup, $targetLevel);
        $plan = new BreedDevelopmentPlan(
            ...get_object_vars($plan),
            trainingModules: $trainingModules,
        );

        // Check prerequisites
        $prerequisites = $this->checkPrerequisites($masterId, $targetBreedGroup, $targetLevel);
        $plan = $plan->markPrerequisitesMet($prerequisites['met']);

        if (! $prerequisites['met']) {
            $plan = new BreedDevelopmentPlan(
                ...get_object_vars($plan),
                prerequisites: $prerequisites['missing'],
            );
        }

        $savedPlan = $this->developmentPlanRepository->save($plan);
        $savedPlan = $savedPlan->start();

        return $this->developmentPlanRepository->save($savedPlan);
    }

    /**
     * Update breed specialization after certification.
     */
    private function updateBreedSpecialization(int $masterId, string $breedGroup, string $certificationLevel, int $tenantId): void
    {
        $specialization = $this->specializationRepository->findByMasterIdAndBreedGroup($masterId, $breedGroup);

        if ($specialization !== null) {
            $updatedSpecialization = $specialization->incrementCertifications();
            $this->specializationRepository->save($updatedSpecialization);
        } else {
            $newSpecialization = BreedSpecialization::create(
                tenantId: $tenantId,
                masterId: $masterId,
                breedGroup: $breedGroup,
                proficiencyLevel: $certificationLevel,
            );
            $this->specializationRepository->save($newSpecialization);
        }
    }

    /**
     * Get breed group for a pet species and breed.
     */
    public function getBreedGroup(string $species, string $breed): ?string
    {
        $speciesLower = strtolower($species);
        $breedLower = strtolower(str_replace([' ', '_'], '_', $breed));

        if (! isset(self::BREED_GROUPS[$speciesLower])) {
            return null;
        }

        foreach (self::BREED_GROUPS[$speciesLower] as $group => $breeds) {
            if (in_array($breedLower, array_map(fn ($b) => strtolower(str_replace([' ', '_'], '_', $b)), $breeds), true)) {
                return $group;
            }
        }

        return null;
    }

    /**
     * Get highest certification level for a groomer and breed.
     */
    private function getHighestCertificationLevel(int $masterId, string $petSpecies, string $petBreed): ?string
    {
        $breedGroup = $this->getBreedGroup($petSpecies, $petBreed);
        if ($breedGroup === null) {
            return null;
        }

        $levels = ['basic' => 1, 'certified' => 2, 'advanced' => 3, 'master' => 4];
        $highestLevel = null;
        $highestValue = 0;

        // Check specific breed certification
        $specificCertification = $this->certificationRepository->findByMasterIdAndBreed($masterId, $petBreed);
        if ($specificCertification !== null && $specificCertification->isActive()) {
            $value = $levels[$specificCertification->certificationLevel] ?? 0;
            if ($value > $highestValue) {
                $highestValue = $value;
                $highestLevel = $specificCertification->certificationLevel;
            }
        }

        // Check breed group certifications
        $groupCertifications = $this->certificationRepository->findByMasterIdAndBreedGroup($masterId, $breedGroup);
        foreach ($groupCertifications as $certification) {
            if ($certification->isActive()) {
                $value = $levels[$certification->certificationLevel] ?? 0;
                if ($value > $highestValue) {
                    $highestValue = $value;
                    $highestLevel = $certification->certificationLevel;
                }
            }
        }

        return $highestLevel;
    }

    /**
     * Get blocking message for breed certification.
     */
    private function getBlockingMessage(?string $breedGroup, ?string $requiredLevel): string
    {
        if ($breedGroup === null) {
            return 'Groomer lacks required certification for this breed.';
        }

        $messages = [
            'brachycephalic' => 'This breed requires Advanced certification due to breathing risks and special handling needs.',
            'terriers' => 'This breed requires at least Certified certification for proper coat handling.',
            'spitz' => 'This breed requires at least Certified certification for double coat management.',
            'long_haired' => 'This breed requires at least Intermediate certification for proper grooming.',
            'hairless' => 'This breed requires Advanced certification for special skin care.',
            'default' => "Groomer lacks required {$requiredLevel} certification for {$breedGroup} breeds.",
        ];

        return $messages[$breedGroup] ?? $messages['default'];
    }

    /**
     * Get training/practical requirements for certification level.
     */
    private function getRequirementsForLevel(string $level): array
    {
        return match ($level) {
            'basic' => ['training_hours' => 10, 'practical_hours' => 20],
            'certified' => ['training_hours' => 20, 'practical_hours' => 50],
            'advanced' => ['training_hours' => 40, 'practical_hours' => 100],
            'master' => ['training_hours' => 60, 'practical_hours' => 200],
            default => ['training_hours' => 20, 'practical_hours' => 50],
        };
    }

    /**
     * Get training modules for breed group and level.
     */
    private function getTrainingModulesForBreedGroup(string $breedGroup, string $level): array
    {
        $modules = [
            'basic' => [
                'Breed characteristics and anatomy',
                'Basic grooming techniques',
                'Safety protocols',
                'Tool handling',
            ],
            'certified' => [
                'Advanced coat care',
                'Breed-specific styling',
                'Behavioral understanding',
                'Health assessment',
            ],
            'advanced' => [
                'Complex breed techniques',
                'Show preparation',
                'Problem-solving for difficult coats',
                'Creative styling',
            ],
            'master' => [
                'Teaching and mentoring',
                'Competition standards',
                'Breed innovation',
                'Industry leadership',
            ],
        ];

        $breedSpecificModules = [
            'brachycephalic' => ['Breathing management', 'Heat stress prevention', 'Special handling'],
            'hairless' => ['Skin care protocols', 'Moisturizing techniques', 'Sun protection'],
            'long_haired' => ['Dematting techniques', 'Coat maintenance', 'De-shedding'],
        ];

        $baseModules = $modules[$level] ?? $modules['certified'];
        $specificModules = $breedSpecificModules[$breedGroup] ?? [];

        return array_merge($baseModules, $specificModules);
    }

    /**
     * Check prerequisites for breed certification.
     */
    private function checkPrerequisites(int $masterId, string $breedGroup, string $targetLevel): array
    {
        $requiredLevels = [
            'certified' => 'basic',
            'advanced' => 'certified',
            'master' => 'advanced',
        ];

        $missing = [];
        $met = true;

        if (isset($requiredLevels[$targetLevel])) {
            $prerequisiteLevel = $requiredLevels[$targetLevel];
            $certifications = $this->certificationRepository->findByMasterIdAndBreedGroup($masterId, $breedGroup);

            $hasPrerequisite = false;
            foreach ($certifications as $certification) {
                if ($certification->isActive() && $certification->certificationLevel === $prerequisiteLevel) {
                    $hasPrerequisite = true;
                    break;
                }
            }

            if (! $hasPrerequisite) {
                $met = false;
                $missing[] = "Must have {$prerequisiteLevel} certification in {$breedGroup}";
            }
        }

        return ['met' => $met, 'missing' => $missing];
    }

    /**
     * Check for expiring breed certifications and process them.
     */
    public function checkExpiringCertifications(int $daysThreshold = 30, int $tenantId): array
    {
        $expiringCertifications = $this->certificationRepository->findExpiringWithin($daysThreshold, $tenantId);
        $expiredCertifications = $this->certificationRepository->findExpired($tenantId);

        $results = [
            'expiring' => count($expiringCertifications),
            'expired' => count($expiredCertifications),
            'processed' => 0,
        ];

        // Process expired certifications
        foreach ($expiredCertifications as $certification) {
            if ($certification->status !== 'expired') {
                $expiredCert = $certification->expire();
                $this->certificationRepository->save($expiredCert);

                Event::dispatch(new BreedCertificationExpired(
                    certification: $expiredCert,
                    masterId: $certification->masterId,
                    tenantId: $certification->tenantId,
                    breedGroup: $certification->breedGroup,
                    breedId: $certification->breedId,
                ));

                $results['processed']++;

                $this->logAction('breed_certification_expired', 'BreedCertification', $certification->id, [
                    'certification_id' => $certification->id,
                    'master_id' => $certification->masterId,
                    'breed_group' => $certification->breedGroup,
                ], $certification->masterId, null);
            }
        }

        return $results;
    }

    /**
     * Update breed specialization with grooming session data.
     */
    public function updateSpecializationWithGrooming(int $masterId, string $petSpecies, string $petBreed, int $tenantId): void
    {
        $breedGroup = $this->getBreedGroup($petSpecies, $petBreed);
        if ($breedGroup === null) {
            return;
        }

        $specialization = $this->specializationRepository->findByMasterIdAndBreedGroup($masterId, $breedGroup);

        if ($specialization !== null) {
            $updatedSpecialization = $specialization->incrementGroomings();
            $this->specializationRepository->save($updatedSpecialization);
        }
    }

    /**
     * Get breed certification matrix for a groomer.
     */
    public function getBreedCertificationMatrix(int $masterId, int $tenantId): array
    {
        $certifications = $this->certificationRepository->findActiveByMasterId($masterId);
        $specializations = $this->specializationRepository->findActiveByMasterId($masterId);

        $matrix = [];

        foreach (self::BREED_GROUPS as $species => $groups) {
            foreach ($groups as $groupName => $breeds) {
                $matrix[$groupName] = [
                    'species' => $species,
                    'breeds' => $breeds,
                    'required_level' => self::REQUIRED_LEVELS[$groupName] ?? 'basic',
                    'certification_level' => null,
                    'certification_type' => null,
                    'expiry_date' => null,
                    'specialization_level' => null,
                    'groomings_completed' => 0,
                ];

                // Find certification for this group
                foreach ($certifications as $certification) {
                    if ($certification->breedGroup === $groupName && $certification->isActive()) {
                        $matrix[$groupName]['certification_level'] = $certification->certificationLevel;
                        $matrix[$groupName]['certification_type'] = $certification->certificationType;
                        $matrix[$groupName]['expiry_date'] = $certification->expiryDate?->toDateString();
                    }
                }

                // Find specialization for this group
                foreach ($specializations as $specialization) {
                    if ($specialization->breedGroup === $groupName) {
                        $matrix[$groupName]['specialization_level'] = $specialization->proficiencyLevel;
                        $matrix[$groupName]['groomings_completed'] = $specialization->totalGroomingsCompleted;
                    }
                }
            }
        }

        return $matrix;
    }
}

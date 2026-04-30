<?php

declare(strict_types=1);

namespace Modules\Contraindications\Application\Services;

use Illuminate\Support\Collection;
use Modules\Contraindications\Domain\DTOs\CompatibilityResult;
use Modules\Contraindications\Domain\Entities\Allergy;
use Modules\Contraindications\Domain\Entities\Contraindication;
use Modules\Contraindications\Domain\Entities\ProductComposition;
use Modules\Contraindications\Domain\Repositories\AllergyRepositoryInterface;
use Modules\Contraindications\Domain\Repositories\ContraindicationRepositoryInterface;
use Modules\Contraindications\Domain\Repositories\ProductCompositionRepositoryInterface;
use Modules\Contraindications\Domain\ValueObjects\Scope;
use App\Services\Security\AuditService;
use App\Traits\WithAuditLogging;

final class ContraindicationService
{
    use WithAuditLogging;

    public function __construct(
        private readonly AllergyRepositoryInterface $allergyRepository,
        private readonly ContraindicationRepositoryInterface $contraindicationRepository,
        private readonly ProductCompositionRepositoryInterface $compositionRepository,
        private readonly AuditService $auditService,
    ) {
    }

    /**
     * Get relevant allergies for a specific service scope
     *
     * @return array<Allergy>
     */
    public function getRelevantAllergiesForUser(int $userId, Scope $scope): array
    {
        return $this->allergyRepository->findRelevantForUser($userId, $scope);
    }

    /**
     * Get relevant allergies for a specific service scope
     *
     * @return array<Allergy>
     */
    public function getRelevantAllergiesForPet(int $petId, Scope $scope): array
    {
        return $this->allergyRepository->findRelevantForPet($petId, $scope);
    }

    /**
     * Get relevant contraindications for a specific service scope
     *
     * @return array<Contraindication>
     */
    public function getRelevantContraindicationsForUser(int $userId, Scope $scope): array
    {
        return $this->contraindicationRepository->findRelevantForUser($userId, $scope);
    }

    /**
     * Get relevant contraindications for a specific service scope
     *
     * @return array<Contraindication>
     */
    public function getRelevantContraindicationsForPet(int $petId, Scope $scope): array
    {
        return $this->contraindicationRepository->findRelevantForPet($petId, $scope);
    }

    /**
     * Check compatibility of a service/product with user allergies/contraindications
     */
    public function checkCompatibilityForUser(
        string $composableType,
        int $composableId,
        int $userId,
        Scope $scope
    ): CompatibilityResult {
        $relevantAllergies = $this->getRelevantAllergiesForUser($userId, $scope);
        $relevantContraindications = $this->getRelevantContraindicationsForUser($userId, $scope);
        $composition = $this->compositionRepository->findByComposable($composableType, $composableId);

        return $this->buildCompatibilityResult(
            $relevantAllergies,
            $relevantContraindications,
            $composition
        );
    }

    /**
     * Check compatibility of a service/product with pet allergies/contraindications
     */
    public function checkCompatibilityForPet(
        string $composableType,
        int $composableId,
        int $petId,
        Scope $scope
    ): CompatibilityResult {
        $relevantAllergies = $this->getRelevantAllergiesForPet($petId, $scope);
        $relevantContraindications = $this->getRelevantContraindicationsForPet($petId, $scope);
        $composition = $this->compositionRepository->findByComposable($composableType, $composableId);

        return $this->buildCompatibilityResult(
            $relevantAllergies,
            $relevantContraindications,
            $composition
        );
    }

    /**
     * Filter recommendations to remove items with conflicts
     *
     * @param Collection<int, mixed> $recommendations
     * @return Collection<int, mixed>
     */
    public function filterSafeRecommendations(
        Collection $recommendations,
        int $userId,
        ?int $petId,
        Scope $scope
    ): Collection {
        return $recommendations->filter(function ($item) use ($userId, $petId, $scope) {
            if ($petId) {
                $result = $this->checkCompatibilityForPet(
                    get_class($item),
                    $item->id,
                    $petId,
                    $scope
                );
            } else {
                $result = $this->checkCompatibilityForUser(
                    get_class($item),
                    $item->id,
                    $userId,
                    $scope
                );
            }

            return $result->isCompatible;
        });
    }

    /**
     * Build compatibility result from allergies, contraindications, and composition
     *
     * @param array<Allergy> $allergies
     * @param array<Contraindication> $contraindications
     */
    private function buildCompatibilityResult(
        array $allergies,
        array $contraindications,
        ?ProductComposition $composition
    ): CompatibilityResult {
        $allergyConflicts = [];
        $contraindicationConflicts = $contraindications;

        if ($composition) {
            foreach ($allergies as $allergy) {
                if ($composition->containsAllergen($allergy->name)) {
                    $allergyConflicts[] = $allergy;
                }
                
                // Check common allergy names
                if ($allergy->name !== strtolower($allergy->name)) {
                    if ($composition->containsAllergen(strtolower($allergy->name))) {
                        $allergyConflicts[] = $allergy;
                    }
                }
            }
        }

        $isCompatible = empty($allergyConflicts) && empty($contraindicationConflicts);

        return new CompatibilityResult(
            isCompatible: $isCompatible,
            allergyConflicts: $allergyConflicts,
            contraindicationConflicts: $contraindicationConflicts,
        );
    }
}

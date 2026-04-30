<?php

declare(strict_types=1);

namespace App\Services\ML;

use Modules\Contraindications\Domain\Repositories\AllergyRepositoryInterface;
use Modules\Contraindications\Domain\Repositories\ContraindicationRepositoryInterface;
use Modules\Contraindications\Domain\ValueObjects\Scope;

final class AllergyFeatureExtractor
{
    public function __construct(
        private readonly AllergyRepositoryInterface $allergyRepository,
        private readonly ContraindicationRepositoryInterface $contraindicationRepository,
    ) {
    }

    public function extract(
        object $entity,
        object $subject,
        Scope $scope
    ): array {
        $features = [
            'subject_type' => $subject instanceof \App\Models\User ? 'user' : 'pet',
            'entity_type' => get_class($entity),
            'scope' => $scope->value,
        ];

        // Extract subject features
        $features = array_merge($features, $this->extractSubjectFeatures($subject));

        // Extract entity features
        $features = array_merge($features, $this->extractEntityFeatures($entity));

        // Extract allergy/contraindication features
        $features = array_merge($features, $this->extractAllergyFeatures($subject, $scope));

        return $features;
    }

    private function extractSubjectFeatures(object $subject): array
    {
        $features = [];

        if ($subject instanceof \App\Models\User) {
            $features['age'] = $subject->age ?? null;
            $features['gender'] = $subject->gender ?? null;
            $features['region'] = $subject->region ?? null;
        } elseif (method_exists($subject, 'age')) {
            $features['age'] = $subject->age;
            $features['species'] = $subject->species ?? null;
            $features['breed'] = $subject->breed ?? null;
            $features['breed_risk_factor'] = $this->getBreedRiskFactor($subject);
        }

        return $features;
    }

    private function extractEntityFeatures(object $entity): array
    {
        $features = [];

        // Extract composition if available
        if (method_exists($entity, 'composition')) {
            $composition = $entity->composition;
            if ($composition) {
                $features['product_ingredients'] = $composition->ingredients ?? [];
                $features['allergens'] = $composition->allergens ?? [];
            }
        }

        // Extract category
        if (method_exists($entity, 'category_id')) {
            $features['category_id'] = $entity->category_id;
        }

        // Extract tags
        if (method_exists($entity, 'tags')) {
            $features['tags'] = $entity->tags;
        }

        return $features;
    }

    private function extractAllergyFeatures(object $subject, Scope $scope): array
    {
        $features = [];

        $subjectId = $subject->id;
        $isPet = !($subject instanceof \App\Models\User);

        // Get existing allergies
        if ($isPet) {
            $allergies = $this->allergyRepository->findActiveByPetId($subjectId);
        } else {
            $allergies = $this->allergyRepository->findActiveByUserId($subjectId);
        }

        $features['existing_allergies'] = array_map(
            fn ($allergy) => strtolower($allergy->name),
            $allergies
        );

        $features['severe_reaction_history'] = collect($allergies)
            ->contains(fn ($allergy) => $allergy->severity->value === 'severe' || 
                                     $allergy->severity->value === 'life_threatening');

        // Get contraindications
        if ($isPet) {
            $contraindications = $this->contraindicationRepository->findActiveByPetId($subjectId);
        } else {
            $contraindications = $this->contraindicationRepository->findActiveByUserId($subjectId);
        }

        $features['contraindications'] = array_map(
            fn ($c) => strtolower($c->name),
            $contraindications
        );

        return $features;
    }

    private function getBreedRiskFactor(object $pet): float
    {
        // Breed-specific allergy risk factors
        $highRiskBreeds = [
            'bulldog', 'boxer', 'pug', 'shar pei', // Skin allergies
            'west highland white terrier', 'cocker spaniel', // Atopy
        ];

        $breed = strtolower($pet->breed ?? '');

        foreach ($highRiskBreeds as $riskBreed) {
            if (str_contains($breed, $riskBreed)) {
                return 0.4;
            }
        }

        return 0.0;
    }
}

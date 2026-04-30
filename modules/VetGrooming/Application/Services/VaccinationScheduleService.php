<?php

declare(strict_types=1);

namespace Modules\VetGrooming\Application\Services;

use App\Traits\WithAuditLogging;
use App\Services\Audit\AuditService;
use Modules\VetGrooming\Domain\Repositories\PetVaccinationRepositoryInterface;
use Modules\VetGrooming\Domain\Repositories\PetChronicConditionRepositoryInterface;
use Modules\VetGrooming\Domain\Entities\PetVaccination;
use Modules\VetGrooming\Domain\Enums\VaccineType;
use Modules\VetGrooming\Domain\Enums\VaccinationStatus;
use Modules\VetGrooming\Domain\Enums\ConditionType;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Log;

/**
 * VaccinationScheduleService - WSAVA 2024 Compliant Vaccination Scheduling
 * 
 * Implements World Small Animal Veterinary Association (WSAVA) 2024 vaccination guidelines
 * for dogs and cats, with Russian Federation legal requirements (mandatory rabies).
 */
final class VaccinationScheduleService
{
    use WithAuditLogging;

    public function __construct(
        private readonly PetVaccinationRepositoryInterface $vaccinationRepository,
        private readonly PetChronicConditionRepositoryInterface $conditionRepository,
        private readonly AuditService $audit,
    ) {}

    /**
     * Generate complete vaccination schedule for a pet based on WSAVA 2024 guidelines
     * 
     * @param int $petId Pet ID
     * @param string $species Species: 'dog', 'cat'
     * @param CarbonImmutable $birthDate Pet's birth date
     * @param array $riskFactors Regional and lifestyle risk factors
     * @return array Generated vaccination schedule
     */
    public function generateSchedule(
        int $petId,
        string $species,
        CarbonImmutable $birthDate,
        array $riskFactors = []
    ): array {
        $schedule = [];
        $ageWeeks = (int) $birthDate->diffInWeeks(CarbonImmutable::now());

        if ($species === 'dog') {
            $schedule = $this->generateDogSchedule($petId, $ageWeeks, $birthDate, $riskFactors);
        } elseif ($species === 'cat') {
            $schedule = $this->generateCatSchedule($petId, $ageWeeks, $birthDate, $riskFactors);
        } else {
            $this->logAction('vaccination_schedule_unsupported_species', 'VaccinationSchedule', null, [
                'species' => $species,
                'pet_id' => $petId,
            ], null, null);
            return [];
        }

        // Check for vaccine allergies
        $allergies = $this->conditionRepository->findActiveAllergiesByPetId($petId);
        foreach ($allergies as $allergy) {
            if ($allergy->isAllergy()) {
                $schedule['allergies'][] = [
                    'condition' => $allergy->conditionName,
                    'type' => $allergy->conditionType->value,
                    'severity' => $allergy->severity,
                    'triggers' => $allergy->triggers,
                ];
            }
        }

        return $schedule;
    }

    /**
     * Generate vaccination schedule for dogs according to WSAVA 2024
     */
    private function generateDogSchedule(
        int $petId,
        int $ageWeeks,
        CarbonImmutable $birthDate,
        array $riskFactors
    ): array {
        $schedule = [
            'species' => 'dog',
            'core_vaccines' => [],
            'non_core_vaccines' => [],
            'recommendations' => [],
        ];

        // Core vaccines: DHP (Distemper, Hepatitis, Parvovirus) + Rabies
        if ($ageWeeks < 16) {
            // Puppy series
            $schedule['core_vaccines'][] = [
                'vaccine_type' => VaccineType::CORE_DHP->value,
                'label' => VaccineType::CORE_DHP->getLabel(),
                'dose_number' => 1,
                'planned_date' => $birthDate->addWeeks(6)->toDateString(),
                'notes' => 'First dose of puppy series',
            ];

            $schedule['core_vaccines'][] = [
                'vaccine_type' => VaccineType::CORE_DHP->value,
                'label' => VaccineType::CORE_DHP->getLabel(),
                'dose_number' => 2,
                'planned_date' => $birthDate->addWeeks(10)->toDateString(),
                'notes' => 'Second dose, add leptospirosis if endemic area',
            ];

            $schedule['core_vaccines'][] = [
                'vaccine_type' => VaccineType::CORE_DHP->value,
                'label' => VaccineType::CORE_DHP->getLabel(),
                'dose_number' => 3,
                'planned_date' => $birthDate->addWeeks(14)->toDateString(),
                'notes' => 'Third dose',
            ];

            $schedule['core_vaccines'][] = [
                'vaccine_type' => VaccineType::RABIES->value,
                'label' => VaccineType::RABIES->getLabel(),
                'dose_number' => 1,
                'planned_date' => $birthDate->addWeeks(14)->toDateString(),
                'notes' => 'Mandatory by Russian law, can be given with DHP',
                'is_mandatory' => true,
            ];
        } elseif ($ageWeeks >= 16 && $ageWeeks < 52) {
            // Young adult - first booster at 12 months
            $schedule['core_vaccines'][] = [
                'vaccine_type' => VaccineType::CORE_DHP->value,
                'label' => VaccineType::CORE_DHP->getLabel(),
                'dose_number' => 4,
                'planned_date' => $birthDate->addYear()->toDateString(),
                'notes' => 'First booster (12 months), then every 3 years',
            ];

            $schedule['core_vaccines'][] = [
                'vaccine_type' => VaccineType::RABIES->value,
                'label' => VaccineType::RABIES->getLabel(),
                'dose_number' => 2,
                'planned_date' => $birthDate->addYear()->toDateString(),
                'notes' => 'Annual booster required by Russian law',
                'is_mandatory' => true,
            ];
        } else {
            // Adult dog
            $lastRabies = $this->vaccinationRepository->findLastVaccination($petId, VaccineType::RABIES);
            $lastDhp = $this->vaccinationRepository->findLastVaccination($petId, VaccineType::CORE_DHP);

            if ($lastRabies) {
                $nextRabies = $lastRabies->actualDate?->addYear();
                $schedule['core_vaccines'][] = [
                    'vaccine_type' => VaccineType::RABIES->value,
                    'label' => VaccineType::RABIES->getLabel(),
                    'planned_date' => $nextRabies?->toDateString(),
                    'last_date' => $lastRabies->actualDate?->toDateString(),
                    'notes' => 'Annual booster required by Russian law',
                    'is_mandatory' => true,
                ];
            }

            if ($lastDhp) {
                // After first booster, DHP can be given every 3 years per WSAVA 2024
                $nextDhp = $lastDhp->actualDate?->addYears(3);
                $schedule['core_vaccines'][] = [
                    'vaccine_type' => VaccineType::CORE_DHP->value,
                    'label' => VaccineType::CORE_DHP->getLabel(),
                    'planned_date' => $nextDhp?->toDateString(),
                    'last_date' => $lastDhp->actualDate?->toDateString(),
                    'notes' => 'Every 3 years after first booster (WSAVA 2024)',
                ];
            }
        }

        // Non-core vaccines based on risk factors
        if (in_array('leptospirosis_risk', $riskFactors) || in_array('endemic_lepto', $riskFactors)) {
            $schedule['non_core_vaccines'][] = [
                'vaccine_type' => VaccineType::LEPTOSPIROSIS->value,
                'label' => VaccineType::LEPTOSPIROSIS->getLabel(),
                'planned_date' => $birthDate->addWeeks(10)->toDateString(),
                'notes' => 'Annual booster required in endemic areas',
                'reason' => 'Regional risk factor',
            ];
        }

        if (in_array('tick_borne_risk', $riskFactors) || in_array('endemic_borrelia', $riskFactors)) {
            $schedule['non_core_vaccines'][] = [
                'vaccine_type' => VaccineType::BORRELIA->value,
                'label' => VaccineType::BORRELIA->getLabel(),
                'planned_date' => $birthDate->addWeeks(12)->toDateString(),
                'notes' => 'Annual booster required',
                'reason' => 'Regional risk factor',
            ];
        }

        if (in_array('kennel_boarding', $riskFactors) || in_array('dog_shows', $riskFactors)) {
            $schedule['non_core_vaccines'][] = [
                'vaccine_type' => VaccineType::KENNEL_COUGH->value,
                'label' => VaccineType::KENNEL_COUGH->getLabel(),
                'planned_date' => $birthDate->addWeeks(12)->toDateString(),
                'notes' => 'Annual or semi-annual booster for high-risk dogs',
                'reason' => 'Lifestyle risk factor',
            ];
        }

        $schedule['recommendations'][] = 'Rabies vaccination is mandatory by Russian Federal Law';
        $schedule['recommendations'][] = 'Core vaccines (DHP) can be given every 3 years after initial series per WSAVA 2024';
        $schedule['recommendations'][] = 'Consider titer testing for adult dogs to assess immunity';

        return $schedule;
    }

    /**
     * Generate vaccination schedule for cats according to WSAVA 2024
     */
    private function generateCatSchedule(
        int $petId,
        int $ageWeeks,
        CarbonImmutable $birthDate,
        array $riskFactors
    ): array {
        $schedule = [
            'species' => 'cat',
            'core_vaccines' => [],
            'non_core_vaccines' => [],
            'recommendations' => [],
        ];

        // Core vaccines: FPV + FCV + FHV-1 + Rabies
        if ($ageWeeks < 16) {
            // Kitten series
            $schedule['core_vaccines'][] = [
                'vaccine_type' => VaccineType::CORE_FPV->value,
                'label' => VaccineType::CORE_FPV->getLabel(),
                'dose_number' => 1,
                'planned_date' => $birthDate->addWeeks(8)->toDateString(),
                'notes' => 'First dose of kitten series',
            ];

            $schedule['core_vaccines'][] = [
                'vaccine_type' => VaccineType::CORE_FCV_FHV1->value,
                'label' => VaccineType::CORE_FCV_FHV1->getLabel(),
                'dose_number' => 1,
                'planned_date' => $birthDate->addWeeks(8)->toDateString(),
                'notes' => 'Can be combined with FPV',
            ];

            $schedule['core_vaccines'][] = [
                'vaccine_type' => VaccineType::CORE_FPV->value,
                'label' => VaccineType::CORE_FPV->getLabel(),
                'dose_number' => 2,
                'planned_date' => $birthDate->addWeeks(12)->toDateString(),
                'notes' => 'Second dose',
            ];

            $schedule['core_vaccines'][] = [
                'vaccine_type' => VaccineType::CORE_FCV_FHV1->value,
                'label' => VaccineType::CORE_FCV_FHV1->getLabel(),
                'dose_number' => 2,
                'planned_date' => $birthDate->addWeeks(12)->toDateString(),
                'notes' => 'Second dose',
            ];

            $schedule['core_vaccines'][] = [
                'vaccine_type' => VaccineType::RABIES->value,
                'label' => VaccineType::RABIES->getLabel(),
                'dose_number' => 1,
                'planned_date' => $birthDate->addWeeks(16)->toDateString(),
                'notes' => 'Mandatory by Russian law',
                'is_mandatory' => true,
            ];
        } elseif ($ageWeeks >= 16 && $ageWeeks < 52) {
            // Young adult - first booster at 12 months
            $schedule['core_vaccines'][] = [
                'vaccine_type' => VaccineType::CORE_FPV->value,
                'label' => VaccineType::CORE_FPV->getLabel(),
                'dose_number' => 3,
                'planned_date' => $birthDate->addYear()->toDateString(),
                'notes' => 'First booster (12 months), then every 3 years',
            ];

            $schedule['core_vaccines'][] = [
                'vaccine_type' => VaccineType::CORE_FCV_FHV1->value,
                'label' => VaccineType::CORE_FCV_FHV1->getLabel(),
                'dose_number' => 3,
                'planned_date' => $birthDate->addYear()->toDateString(),
                'notes' => 'First booster (12 months), then every 3 years',
            ];

            $schedule['core_vaccines'][] = [
                'vaccine_type' => VaccineType::RABIES->value,
                'label' => VaccineType::RABIES->getLabel(),
                'dose_number' => 2,
                'planned_date' => $birthDate->addYear()->toDateString(),
                'notes' => 'Annual booster required by Russian law',
                'is_mandatory' => true,
            ];
        } else {
            // Adult cat
            $lastRabies = $this->vaccinationRepository->findLastVaccination($petId, VaccineType::RABIES);
            $lastFpv = $this->vaccinationRepository->findLastVaccination($petId, VaccineType::CORE_FPV);
            $lastFcv = $this->vaccinationRepository->findLastVaccination($petId, VaccineType::CORE_FCV_FHV1);

            if ($lastRabies) {
                $nextRabies = $lastRabies->actualDate?->addYear();
                $schedule['core_vaccines'][] = [
                    'vaccine_type' => VaccineType::RABIES->value,
                    'label' => VaccineType::RABIES->getLabel(),
                    'planned_date' => $nextRabies?->toDateString(),
                    'last_date' => $lastRabies->actualDate?->toDateString(),
                    'notes' => 'Annual booster required by Russian law',
                    'is_mandatory' => true,
                ];
            }

            if ($lastFpv) {
                $nextFpv = $lastFpv->actualDate?->addYears(3);
                $schedule['core_vaccines'][] = [
                    'vaccine_type' => VaccineType::CORE_FPV->value,
                    'label' => VaccineType::CORE_FPV->getLabel(),
                    'planned_date' => $nextFpv?->toDateString(),
                    'last_date' => $lastFpv->actualDate?->toDateString(),
                    'notes' => 'Every 3 years after first booster (WSAVA 2024)',
                ];
            }

            if ($lastFcv) {
                $nextFcv = $lastFcv->actualDate?->addYears(3);
                $schedule['core_vaccines'][] = [
                    'vaccine_type' => VaccineType::CORE_FCV_FHV1->value,
                    'label' => VaccineType::CORE_FCV_FHV1->getLabel(),
                    'planned_date' => $nextFcv?->toDateString(),
                    'last_date' => $lastFcv->actualDate?->toDateString(),
                    'notes' => 'Every 3 years after first booster (WSAVA 2024)',
                ];
            }
        }

        // Non-core vaccines based on risk factors
        if (in_array('outdoor_cat', $riskFactors) || in_array('multi_cat_household', $riskFactors)) {
            $schedule['non_core_vaccines'][] = [
                'vaccine_type' => VaccineType::FELV->value,
                'label' => VaccineType::FELV->getLabel(),
                'planned_date' => $birthDate->addWeeks(12)->toDateString(),
                'notes' => 'Annual booster for at-risk cats',
                'reason' => 'Lifestyle risk factor',
            ];
        }

        $schedule['recommendations'][] = 'Rabies vaccination is mandatory by Russian Federal Law';
        $schedule['recommendations'][] = 'Core vaccines can be given every 3 years after initial series per WSAVA 2024';
        $schedule['recommendations'][] = 'Indoor-only cats may have reduced vaccination frequency after initial series';

        return $schedule;
    }

    /**
     * Get next due vaccinations for a pet
     */
    public function getNextDueVaccinations(int $petId): array
    {
        $due = $this->vaccinationRepository->findDueWithinDays($petId, 30);
        $overdue = $this->vaccinationRepository->findOverdueByPetId($petId);

        return [
            'due_soon' => $due,
            'overdue' => $overdue,
            'total_due' => count($due) + count($overdue),
        ];
    }

    /**
     * Check for overdue vaccinations
     */
    public function checkOverdue(int $petId): array
    {
        return $this->vaccinationRepository->findOverdueByPetId($petId);
    }

    /**
     * Mark vaccination as completed and calculate next due date
     */
    public function markAsCompleted(
        int $vaccinationId,
        CarbonImmutable $actualDate,
        ?string $notes = null,
        ?array $reactionData = null
    ): PetVaccination {
        $vaccination = $this->vaccinationRepository->findById($vaccinationId);
        if (! $vaccination) {
            throw new \InvalidArgumentException("Vaccination not found: {$vaccinationId}");
        }

        // Calculate next due date based on vaccine type
        $nextDueDate = $this->calculateNextDueDate($vaccination->vaccineType, $actualDate);

        $vaccination = $vaccination->markAsCompleted($actualDate, $nextDueDate, $notes);

        if ($reactionData) {
            $vaccination = $vaccination->recordReaction($reactionData);
            
            // If adverse reaction, record as allergy
            if (! empty($reactionData['severity']) && $reactionData['severity'] === 'severe') {
                $this->recordVaccineAllergy($vaccination->petId, $vaccination->vaccineName, $reactionData);
            }
        }

        return $this->vaccinationRepository->save($vaccination);
    }

    /**
     * Calculate next due date based on WSAVA 2024 guidelines
     */
    private function calculateNextDueDate(VaccineType $vaccineType, CarbonImmutable $actualDate): CarbonImmutable
    {
        $intervalDays = $vaccineType->getStandardIntervalDays();
        return $actualDate->addDays($intervalDays);
    }

    /**
     * Record vaccine allergy in chronic conditions
     */
    private function recordVaccineAllergy(int $petId, string $vaccineName, array $reactionData): void
    {
        // This would create a new chronic condition entry for the allergy
        // Implementation depends on the ChronicCondition entity structure
        $this->logAction('vaccine_allergy_recorded', 'PetVaccination', $petId, [
            'vaccine_name' => $vaccineName,
            'reaction_data' => $reactionData,
        ], null, null);
    }

    /**
     * Get quick vaccination summary for vet dashboard
     */
    public function getQuickSummary(int $petId): array
    {
        $nextDue = $this->vaccinationRepository->findNextDueVaccination($petId);
        $overdue = $this->vaccinationRepository->findOverdueByPetId($petId);
        $rabies = $this->vaccinationRepository->findLastVaccination($petId, VaccineType::RABIES);

        $isRabiesCurrent = false;
        if ($rabies && $rabies->actualDate) {
            $isRabiesCurrent = $rabies->actualDate->addYear()->isFuture();
        }

        return [
            'next_vaccination' => $nextDue,
            'overdue_count' => count($overdue),
            'rabies_current' => $isRabiesCurrent,
            'last_rabies_date' => $rabies?->actualDate?->toDateString(),
            'rabies_expires' => $rabies?->actualDate?->addYear()->toDateString(),
        ];
    }

    /**
     * Check if medications are safe given pet's allergies
     */
    public function checkMedicationAllergies(int $petId, array $medications): array
    {
        $allergies = $this->conditionRepository->findActiveAllergiesByPetId($petId);
        $warnings = [];

        foreach ($allergies as $allergy) {
            if ($allergy->conditionType === ConditionType::ALLERGY_MEDICATION) {
                foreach ($medications as $medication) {
                    if (str_contains(strtolower($medication), strtolower($allergy->conditionName))) {
                        $warnings[] = [
                            'medication' => $medication,
                            'allergen' => $allergy->conditionName,
                            'severity' => $allergy->severity,
                            'notes' => $allergy->description,
                        ];
                    }
                }
            }
        }

        return $warnings;
    }
}

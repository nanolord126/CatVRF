<?php

declare(strict_types=1);

namespace Modules\Hotels\Application\Services;

use Modules\Hotels\Domain\Entities\LoyaltyProgram;
use Modules\Hotels\Domain\Enums\GuestLoyaltyLevel;
use Modules\Hotels\Domain\Repositories\LoyaltyProgramRepositoryInterface;
use Modules\Hotels\Domain\ValueObjects\VenueId;
use Modules\Hotels\Domain\ValueObjects\TenantId;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Log;
use App\Traits\WithAuditLogging;
use App\Services\Security\AuditService;

final readonly class LoyaltyProgramService
{
    use WithAuditLogging;

    public function __construct(
        private LoyaltyProgramRepositoryInterface $loyaltyProgramRepository,
        private readonly AuditService $audit,
    ) {}

    public function createProgram(
        TenantId $tenantId,
        VenueId $venueId,
        string $name,
        GuestLoyaltyLevel $level,
        int $pointsPerNight = 10,
        float $pointsToRublesRate = 0.01,
        ?string $description = null,
        ?array $benefits = null,
        ?CarbonImmutable $validFrom = null,
        ?CarbonImmutable $validUntil = null,
    ): LoyaltyProgram {
        $program = LoyaltyProgram::create(
            tenantId: $tenantId,
            venueId: $venueId,
            name: $name,
            level: $level,
            pointsPerNight: $pointsPerNight,
            pointsToRublesRate: $pointsToRublesRate,
            description: $description,
            benefits: $benefits,
            validFrom: $validFrom,
            validUntil: $validUntil,
        );

        $saved = $this->loyaltyProgramRepository->save($program);

        Log::info('Loyalty program created', [
            'program_id' => $program->id,
            'venue_id' => $venueId->value,
            'name' => $name,
            'level' => $level->value,
        ]);

        $this->logCreated('LoyaltyProgram', $saved->id, [
            'name' => $name,
            'level' => $level->value,
        ], null, $tenantId->value);

        return $saved;
    }

    public function activateProgram(int $programId): LoyaltyProgram
    {
        $program = $this->loyaltyProgramRepository->findById(\Modules\Hotels\Domain\ValueObjects\LoyaltyProgramId::fromInt($programId));
        if ($program === null) {
            throw new \InvalidArgumentException('Loyalty program not found');
        }

        $updatedProgram = $program->activate();
        $this->loyaltyProgramRepository->save($updatedProgram);

        return $updatedProgram;
    }

    public function deactivateProgram(int $programId): LoyaltyProgram
    {
        $program = $this->loyaltyProgramRepository->findById(\Modules\Hotels\Domain\ValueObjects\LoyaltyProgramId::fromInt($programId));
        if ($program === null) {
            throw new \InvalidArgumentException('Loyalty program not found');
        }

        $updatedProgram = $program->deactivate();
        $this->loyaltyProgramRepository->save($updatedProgram);

        return $updatedProgram;
    }

    public function calculatePoints(int $programId, int $nights): int
    {
        $program = $this->loyaltyProgramRepository->findById(\Modules\Hotels\Domain\ValueObjects\LoyaltyProgramId::fromInt($programId));
        if ($program === null) {
            throw new \InvalidArgumentException('Loyalty program not found');
        }

        return $program->calculatePoints($nights);
    }

    public function pointsToRubles(int $programId, int $points): float
    {
        $program = $this->loyaltyProgramRepository->findById(\Modules\Hotels\Domain\ValueObjects\LoyaltyProgramId::fromInt($programId));
        if ($program === null) {
            throw new \InvalidArgumentException('Loyalty program not found');
        }

        return $program->pointsToRubles($points);
    }

    public function rublesToPoints(int $programId, float $rubles): int
    {
        $program = $this->loyaltyProgramRepository->findById(\Modules\Hotels\Domain\ValueObjects\LoyaltyProgramId::fromInt($programId));
        if ($program === null) {
            throw new \InvalidArgumentException('Loyalty program not found');
        }

        return $program->rublesToPoints($rubles);
    }

    public function getProgramByLevel(VenueId $venueId, GuestLoyaltyLevel $level): ?LoyaltyProgram
    {
        return $this->loyaltyProgramRepository->findByVenueAndLevel($venueId, $level);
    }

    public function getActivePrograms(VenueId $venueId): array
    {
        return $this->loyaltyProgramRepository->findActiveByVenue($venueId);
    }

    public function applyDiscount(int $programId, float $totalAmount): float
    {
        $program = $this->loyaltyProgramRepository->findById(\Modules\Hotels\Domain\ValueObjects\LoyaltyProgramId::fromInt($programId));
        if ($program === null) {
            throw new \InvalidArgumentException('Loyalty program not found');
        }

        $discountPercent = $program->getDiscountPercent();
        $discountAmount = $totalAmount * ($discountPercent / 100);

        return $totalAmount - $discountAmount;
    }
}

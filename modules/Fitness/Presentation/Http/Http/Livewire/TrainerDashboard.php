<?php

declare(strict_types=1);

namespace Modules\Fitness\Http\Livewire;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Modules\Fitness\Application\Services\TrainerCertificationService;
use Modules\Fitness\Application\Services\TrainerEffectivenessService;
use Modules\Fitness\Infrastructure\Models\TrainerCertificationModel;
use Modules\Fitness\Infrastructure\Models\TrainerEffectivenessModel;
use Modules\Fitness\Infrastructure\Models\TrainerModel;

final class TrainerDashboard extends Component
{
    public int $trainerId;
    public array $effectivenessData = [];
    public array $certifications = [];
    public array $specializations = [];
    public array $upcomingExpirations = [];
    public string $qualificationLevel = '';
    public bool $isOnHold = false;
    public string $onHoldReason = '';

    public function mount(): void
    {
        $user = Auth::user();
        if (!$user) {
            return;
        }

        $trainer = TrainerModel::where('user_id', $user->id)->first();
        if (!$trainer) {
            return;
        }

        $this->trainerId = $trainer->id;
        $this->loadDashboardData();
    }

    public function loadDashboardData(): void
    {
        $trainer = TrainerModel::find($this->trainerId);
        if (!$trainer) {
            return;
        }

        $effectivenessService = app(TrainerEffectivenessService::class);
        $certificationService = app(TrainerCertificationService::class);

        // Load effectiveness data
        $latestEffectiveness = TrainerEffectivenessModel::where('trainer_id', $this->trainerId)
            ->orderBy('period_end', 'desc')
            ->first();

        if ($latestEffectiveness) {
            $entity = $latestEffectiveness->toDomain();
            $this->effectivenessData = [
                'total_score' => $entity->totalScore,
                'effectiveness_level' => $entity->effectivenessLevel,
                'retention_rate' => $entity->retentionRate,
                'nps_score' => $entity->npsScore,
                'avg_check' => $entity->avgCheckPerClient,
                'occupancy_rate' => $entity->occupancyRate,
                'manager_score' => $entity->managerScore,
                'period_end' => $entity->periodEnd->format('d.m.Y'),
                'is_top_performer' => $entity->isTopPerformer(),
                'requires_attention' => $entity->requiresAttention(),
                'is_critical' => $entity->isCritical(),
            ];
        }

        // Load certifications
        $this->certifications = TrainerCertificationModel::where('trainer_id', $this->trainerId)
            ->where('status', 'active')
            ->get()
            ->map(function ($cert) {
                $entity = $cert->toDomain();
                return [
                    'name' => $entity->name,
                    'type' => $entity->certificationType,
                    'issuer' => $entity->issuer,
                    'issue_date' => $entity->issueDate->format('d.m.Y'),
                    'expiry_date' => $entity->expiryDate ? $entity->expiryDate->format('d.m.Y') : null,
                    'is_expiring_soon' => $entity->isExpiringSoon(30),
                    'days_until_expiry' => $entity->expiryDate
                        ? CarbonImmutable::now()->diffInDays($entity->expiryDate)
                        : null,
                ];
            })
            ->toArray();

        // Load specializations
        $this->specializations = $trainer->trainerSpecializations()
            ->where('is_active', true)
            ->get()
            ->map(function ($spec) {
                $entity = $spec->toDomain();
                return [
                    'specialization' => $entity->specialization,
                    'level' => $entity->level,
                    'expiry_date' => $entity->expiryDate ? $entity->expiryDate->format('d.m.Y') : null,
                    'is_expired' => $entity->isExpired(),
                ];
            })
            ->toArray();

        // Load upcoming expirations
        $results = $certificationService->checkExpiringCertificates();
        $this->upcomingExpirations = array_merge(
            array_map(fn ($cert) => [
                'type' => 'certification',
                'name' => $cert->name,
                'expiry_date' => $cert->expiryDate ? $cert->expiryDate->format('d.m.Y') : null,
            ], $results['expiring_soon']),
            array_map(fn ($spec) => [
                'type' => 'specialization',
                'name' => $spec->specialization,
                'expiry_date' => $spec->expiryDate ? $spec->expiryDate->format('d.m.Y') : null,
            ], $results['expiring_specializations'])
        );

        // Load trainer status
        $this->qualificationLevel = $trainer->qualification_level ?? 'junior';
        $this->isOnHold = (bool) $trainer->is_on_hold;
        $this->onHoldReason = $trainer->on_hold_reason ?? '';
    }

    public function requestEffectivenessRecalculation(): void
    {
        \Modules\Fitness\Application\Jobs\UpdateTrainerEffectivenessJob::dispatch($this->trainerId);
        $this->dispatch('notification', message: 'Запрошен пересчет эффективности', type: 'success');
    }

    public function render()
    {
        return view('fitness::livewire.trainer-dashboard');
    }
}

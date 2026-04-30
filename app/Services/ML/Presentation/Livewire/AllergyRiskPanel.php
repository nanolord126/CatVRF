<?php

declare(strict_types=1);

namespace App\Services\ML\Presentation\Livewire;

use Livewire\Component;
use App\Services\ML\MLAllergyService;
use App\Services\ML\DTOs\RiskPrediction;
use App\Services\ML\Enums\RiskLevel;
use Modules\Contraindications\Domain\ValueObjects\Scope;

final class AllergyRiskPanel extends Component
{
    public object $entity;
    public object $subject;
    public string $scope;

    public ?RiskPrediction $riskPrediction = null;
    public bool $loading = false;
    public bool $showDetails = false;

    public function mount(object $entity, object $subject, string $scope = 'medical'): void
    {
        $this->entity = $entity;
        $this->subject = $subject;
        $this->scope = $scope;
    }

    public function loadRiskPrediction(): void
    {
        $this->loading = true;

        try {
            $service = app(MLAllergyService::class);
            $scopeEnum = Scope::from($this->scope);
            
            $this->riskPrediction = $service->predictRisk(
                $this->entity,
                $this->subject,
                $scopeEnum
            );
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to load risk prediction');
        } finally {
            $this->loading = false;
        }
    }

    public function getRiskColorProperty(): string
    {
        if (!$this->riskPrediction) {
            return 'gray';
        }

        return match ($this->riskPrediction->riskLevel) {
            RiskLevel::Critical => 'red',
            RiskLevel::High => 'orange',
            RiskLevel::Medium => 'yellow',
            RiskLevel::Low => 'green',
        };
    }

    public function getRiskIconProperty(): string
    {
        if (!$this->riskPrediction) {
            return 'heroicon-o-question-mark-circle';
        }

        return match ($this->riskPrediction->riskLevel) {
            RiskLevel::Critical => 'heroicon-o-exclamation-circle',
            RiskLevel::High => 'heroicon-o-exclamation-triangle',
            RiskLevel::Medium => 'heroicon-o-information-circle',
            RiskLevel::Low => 'heroicon-o-check-circle',
        };
    }

    public function getShouldBlockProperty(): bool
    {
        if (!$this->riskPrediction) {
            return false;
        }

        return $this->riskPrediction->shouldBlock();
    }

    public function render()
    {
        return view('ml::livewire.allergy-risk-panel');
    }
}

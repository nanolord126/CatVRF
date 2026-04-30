<?php

declare(strict_types=1);

namespace Modules\VetGrooming\Filament\Components;

use Livewire\Component;
use Modules\VetGrooming\Domain\Entities\ExoticSafetyProtocol;
use Modules\VetGrooming\Domain\Enums\ExoticCategory;
use Modules\VetGrooming\Domain\Repositories\ExoticSafetyProtocolRepositoryInterface;

final class SafetyProtocolChecklist extends Component
{
    public int $tenantId;
    public ExoticCategory $category;
    public string $species;
    public array $checklist = [];
    public ?ExoticSafetyProtocol $protocol = null;
    public bool $isComplete = false;
    public string $validationMessage = '';

    public function mount(int $tenantId, ExoticCategory $category, string $species, ?array $existingChecklist = null): void
    {
        $this->tenantId = $tenantId;
        $this->category = $category;
        $this->species = $species;
        
        $repository = app(ExoticSafetyProtocolRepositoryInterface::class);
        $this->protocol = $repository->findByCategoryAndSpecies($category, $species, $tenantId);
        
        if ($this->protocol) {
            $this->checklist = $existingChecklist ?? array_fill_keys(
                array_keys($this->protocol->checklistItems),
                false
            );
        }
        
        $this->validateCompleteness();
    }

    public function updatedChecklist(): void
    {
        $this->validateCompleteness();
        $this->emit('checklistUpdated', $this->checklist);
    }

    public function validateCompleteness(): void
    {
        if (!$this->protocol) {
            $this->isComplete = false;
            $this->validationMessage = 'Протокол не найден для данного вида';
            return;
        }

        $requiredItems = array_keys($this->protocol->checklistItems);
        $allChecked = true;
        
        foreach ($requiredItems as $item) {
            if (!isset($this->checklist[$item]) || !$this->checklist[$item]) {
                $allChecked = false;
                break;
            }
        }

        $this->isComplete = $allChecked;
        $this->validationMessage = $allComplete 
            ? 'Протокол выполнен полностью' 
            : 'Необходимо выполнить все пункты протокола';
    }

    public function getProtocolItemsProperty(): array
    {
        return $this->protocol?->checklistItems ?? [];
    }

    public function getRiskFactorsProperty(): array
    {
        return $this->protocol?->riskFactors ?? [];
    }

    public function getRequiredEquipmentProperty(): array
    {
        return $this->protocol?->requiredEquipment ?? [];
    }

    public function render()
    {
        return view('vetgrooming::components.safety-protocol-checklist');
    }
}

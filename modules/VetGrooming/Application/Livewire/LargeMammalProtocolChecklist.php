<?php

declare(strict_types=1);

namespace Modules\VetGrooming\Application\Livewire;

use Livewire\Component;
use Modules\VetGrooming\Application\Services\ExoticGroomingService;

final class LargeMammalProtocolChecklist extends Component
{
    public string $mammalGroup;
    public int $aggressionLevel;
    public int $tenantId;
    public array $checklist = [];
    public bool $allRequiredCompleted = false;
    public bool $requiresSecondGroomer = false;
    public bool $requiresVeterinarian = false;
    public array $specificRisks = [];
    public array $safetyPrecautions = [];

    public function mount(string $mammalGroup, int $aggressionLevel, int $tenantId): void
    {
        $this->mammalGroup = $mammalGroup;
        $this->aggressionLevel = $aggressionLevel;
        $this->tenantId = $tenantId;
        $this->loadProtocol();
        $this->assessSafetyRequirements();
    }

    public function loadProtocol(): void
    {
        $service = app(ExoticGroomingService::class);
        $protocol = $service->getSafetyProtocol(
            \Modules\VetGrooming\Domain\Enums\ExoticCategory::LARGE_MAMMALS,
            $this->mammalGroup,
            $this->tenantId
        );

        if ($protocol) {
            $this->initializeChecklist($protocol);
            $this->loadProtocolDetails($protocol);
        } else {
            $this->loadDefaultChecklist();
        }
    }

    public function initializeChecklist($protocol): void
    {
        $items = $protocol->checklistItems ?? [];
        foreach ($items as $item) {
            $key = $item['item'] ?? $item;
            $this->checklist[$key] = false;
        }
    }

    public function loadDefaultChecklist(): void
    {
        $defaultChecklists = [
            'large_dog' => [
                'Оценка агрессии перед началом',
                'Наличие намордника или Gentle Leader',
                'Проверка суставов и лап',
                'Контроль температуры',
                'Фотографирование состояния шерсти до',
            ],
            'giant_dog' => [
                'Оценка агрессии (обязательно)',
                'Обязательный намордник',
                'Двухгрумерная фиксация',
                'Проверка суставов',
                'Контроль температуры (перегрев)',
                'Фотографирование до',
                'Наличие второго грумера',
            ],
            'large_cat' => [
                'Использование специальных сумок-фиксаторов',
                'Минимальный стресс (тихое помещение)',
                'Особая осторожность с хвостом и ушами',
                'Контроль дыхания и сердцебиения',
            ],
        ];

        $items = $defaultChecklists[$this->mammalGroup] ?? [];
        foreach ($items as $item) {
            $this->checklist[$item] = false;
        }
    }

    public function loadProtocolDetails($protocol): void
    {
        $this->specificRisks = $protocol->specificRisks ?? [];
        $this->safetyPrecautions = $protocol->safetyPrecautions ?? [];
    }

    public function assessSafetyRequirements(): void
    {
        $this->requiresSecondGroomer = $this->aggressionLevel >= 6;
        $this->requiresVeterinarian = $this->aggressionLevel >= 8;

        if ($this->requiresSecondGroomer) {
            $this->checklist['Наличие второго грумера'] = false;
        }

        if ($this->requiresVeterinarian) {
            $this->checklist['Наличие ветеринара на подхвате'] = false;
        }
    }

    public function toggleItem(string $item): void
    {
        $this->checklist[$item] = !$this->checklist[$item];
        $this->validateCompleteness();
    }

    public function validateCompleteness(): void
    {
        $this->allRequiredCompleted = !in_array(false, $this->checklist, true);
    }

    public function getProgressProperty(): float
    {
        if (empty($this->checklist)) {
            return 0.0;
        }

        $completed = count(array_filter($this->checklist));
        return ($completed / count($this->checklist)) * 100;
    }

    public function getAggressionColorProperty(): string
    {
        return match (true) {
            $this->aggressionLevel >= 8 => 'red',
            $this->aggressionLevel >= 6 => 'orange',
            $this->aggressionLevel >= 4 => 'yellow',
            default => 'green',
        };
    }

    public function getAggressionLabelProperty(): string
    {
        return match (true) {
            $this->aggressionLevel >= 8 => 'Критическая',
            $this->aggressionLevel >= 6 => 'Высокая',
            $this->aggressionLevel >= 4 => 'Средняя',
            default => 'Низкая',
        };
    }

    public function render()
    {
        return view('vetgrooming::livewire.large-mammal-protocol-checklist');
    }
}

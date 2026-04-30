<?php

declare(strict_types=1);

namespace Modules\VetGrooming\Application\Livewire;

use Livewire\Component;
use Modules\VetGrooming\Application\Services\ExoticGroomingService;

final class SmallMammalProtocolChecklist extends Component
{
    public string $mammalGroup;
    public int $tenantId;
    public array $checklist = [];
    public bool $allRequiredCompleted = false;
    public array $specificRisks = [];
    public array $prohibitedActions = [];
    public array $temperatureRequirements = [];
    public array $handlingRequirements = [];

    public function mount(string $mammalGroup, int $tenantId): void
    {
        $this->mammalGroup = $mammalGroup;
        $this->tenantId = $tenantId;
        $this->loadProtocol();
    }

    public function loadProtocol(): void
    {
        $service = app(ExoticGroomingService::class);
        $protocol = $service->getSafetyProtocol(
            \Modules\VetGrooming\Domain\Enums\ExoticCategory::SMALL_MAMMALS,
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
            'ferret' => [
                'Проверка анальных желез',
                'Оценка запаха и состояния кожи',
                'Проверка на наличие паразитов',
                'Оценка темперамента',
                'Подготовка полотенца для фиксации',
                'Проверка температуры помещения (20-22°C)',
                'Подготовка перчаток',
                'Наличие аптечки',
            ],
            'rabbit' => [
                'Никогда не поднимать за уши!',
                'Проверка зубов и когтей',
                'Особая осторожность с длинношерстными',
                'Профилактика стаза ЖКТ',
                'Проверка температуры помещения',
                'Подготовка фиксации',
            ],
            'chinchilla' => [
                'Проверка состояния шерсти (колтуны)',
                'Оценка хвоста (риск автотомии)',
                'Проверка дыхания',
                'Подготовка специального песка (никогда вода!)',
                'Проверка глаз и ушей',
                'Контроль пыли',
                'Нежная фиксация хвоста',
            ],
            'guinea_pig' => [
                'Проверка длины зубов',
                'Особая осторожность с кожей',
                'Групповой груминг только для знакомых',
                'Проверка на дерматиты',
                'Температурный контроль',
            ],
            'hedgehog' => [
                'Работа только в перчатках',
                'Контроль температуры',
                'Обработка игл без повреждения',
                'Проверка на переохлаждение',
                'Специальная фиксация',
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
        $this->prohibitedActions = $protocol->prohibitedActions ?? [];
        $this->temperatureRequirements = $protocol->temperatureRequirements ?? [];
        $this->handlingRequirements = $protocol->handlingRequirements ?? [];
    }

    public function toggleItem(string $item): void
    {
        $this->checklist[$item] = !$this->checklist[$item];
        $this->validateCompleteness();
    }

    public function validateCompleteness(): void
    {
        // All items are required for small mammals
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

    public function render()
    {
        return view('vetgrooming::livewire.small-mammal-protocol-checklist');
    }
}

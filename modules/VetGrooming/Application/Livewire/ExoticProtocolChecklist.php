<?php

declare(strict_types=1);

namespace Modules\VetGrooming\Application\Livewire;

use Livewire\Component;
use Modules\VetGrooming\Domain\Enums\ExoticCategory;
use Modules\VetGrooming\Application\Services\ExoticGroomingService;
use Modules\VetGrooming\Domain\Entities\ExoticSafetyProtocol;

final class ExoticProtocolChecklist extends Component
{
    public ExoticCategory $category;
    public string $species;
    public int $tenantId;
    public array $checklist = [];
    public bool $allRequiredCompleted = false;
    public ?ExoticSafetyProtocol $protocol = null;

    public function mount(ExoticCategory $category, string $species, int $tenantId): void
    {
        $this->category = $category;
        $this->species = $species;
        $this->tenantId = $tenantId;
        $this->loadProtocol();
    }

    public function loadProtocol(): void
    {
        $service = app(ExoticGroomingService::class);
        $this->protocol = $service->getSafetyProtocol($this->category, $this->species, $this->tenantId);

        if ($this->protocol) {
            $this->initializeChecklist();
        }
    }

    public function initializeChecklist(): void
    {
        if (!$this->protocol) {
            return;
        }

        $items = $this->protocol->checklistItems ?? [];
        foreach ($items as $item) {
            $key = $item['item'] ?? $item;
            $this->checklist[$key] = false;
        }
    }

    public function toggleItem(string $item): void
    {
        $this->checklist[$item] = !$this->checklist[$item];
        $this->validateCompleteness();
    }

    public function validateCompleteness(): void
    {
        if (!$this->protocol) {
            return;
        }

        $items = $this->protocol->checklistItems ?? [];
        $requiredItems = collect($items)
            ->filter(fn ($item) => ($item['is_required'] ?? true))
            ->pluck('item', 'item')
            ->toArray();

        $this->allRequiredCompleted = true;
        foreach ($requiredItems as $item) {
            if (!($this->checklist[$item] ?? false)) {
                $this->allRequiredCompleted = false;
                break;
            }
        }
    }

    public function getProgressProperty(): float
    {
        if (empty($this->checklist)) {
            return 0.0;
        }

        $completed = count(array_filter($this->checklist));
        return ($completed / count($this->checklist)) * 100;
    }

    public function getRequiredItemsProperty(): array
    {
        if (!$this->protocol) {
            return [];
        }

        $items = $this->protocol->checklistItems ?? [];
        return collect($items)
            ->filter(fn ($item) => ($item['is_required'] ?? true))
            ->pluck('item', 'item')
            ->toArray();
    }

    public function render()
    {
        return view('vetgrooming::livewire.exotic-protocol-checklist');
    }
}

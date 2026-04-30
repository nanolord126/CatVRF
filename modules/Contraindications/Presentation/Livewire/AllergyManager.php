<?php

declare(strict_types=1);

namespace Modules\Contraindications\Presentation\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use Modules\Contraindications\Domain\Enums\AllergySeverity;
use Modules\Contraindications\Infrastructure\Models\AllergyModel;

final class AllergyManager extends Component
{
    use WithPagination;

    public int $userId;
    public ?int $petId = null;

    public string $name = '';
    public string $severity = 'moderate';
    public ?string $reaction = null;
    public array $scopes = [];

    public bool $isEditing = false;
    public ?int $editingId = null;

    protected $rules = [
        'name' => 'required|string|max:255',
        'severity' => 'required|in:mild,moderate,severe,life_threatening',
        'reaction' => 'nullable|string',
        'scopes' => 'array',
    ];

    public function mount(int $userId, ?int $petId = null): void
    {
        $this->userId = $userId;
        $this->petId = $petId;
    }

    public function save(): void
    {
        $this->validate();

        AllergyModel::updateOrCreate(
            ['id' => $this->editingId],
            [
                'tenant_id' => tenant()->id,
                'user_id' => $this->userId,
                'pet_id' => $this->petId,
                'name' => $this->name,
                'severity' => $this->severity,
                'reaction' => $this->reaction,
                'scopes' => $this->scopes,
                'is_active' => true,
            ]
        );

        $this->reset(['name', 'severity', 'reaction', 'scopes', 'isEditing', 'editingId']);
        $this->dispatch('allergy-saved');
    }

    public function edit(int $id): void
    {
        $allergy = AllergyModel::findOrFail($id);
        $this->editingId = $id;
        $this->name = $allergy->name;
        $this->severity = $allergy->severity;
        $this->reaction = $allergy->reaction;
        $this->scopes = $allergy->scopes;
        $this->isEditing = true;
    }

    public function delete(int $id): void
    {
        AllergyModel::findOrFail($id)->delete();
        $this->dispatch('allergy-deleted');
    }

    public function cancel(): void
    {
        $this->reset(['name', 'severity', 'reaction', 'scopes', 'isEditing', 'editingId']);
    }

    public function render()
    {
        $query = AllergyModel::where('user_id', $this->userId)
            ->when($this->petId, fn ($q) => $q->where('pet_id', $this->petId))
            ->active();

        $allergies = $query->paginate(10);

        return view('contraindications::livewire.allergy-manager', [
            'allergies' => $allergies,
        ]);
    }
}

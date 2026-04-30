<?php

declare(strict_types=1);

namespace Modules\VetGrooming\Application\Livewire;

use Livewire\Component;
use Modules\VetGrooming\Application\Services\BreedCertificationService;
use Illuminate\Support\Facades\Auth;

class BreedCertificationMatrix extends Component
{
    public int $masterId;
    public ?string $masterName = null;
    public array $certificationMatrix = [];
    public int $tenantId;

    public function mount(int $masterId = null): void
    {
        $this->tenantId = Auth::user()?->tenant_id ?? 0;
        $this->masterId = $masterId ?? Auth::user()?->id ?? 0;

        if ($this->masterId > 0) {
            $this->loadCertificationMatrix();
        }
    }

    public function loadCertificationMatrix(): void
    {
        $service = app(BreedCertificationService::class);
        $this->certificationMatrix = $service->getBreedCertificationMatrix($this->masterId, $this->tenantId);

        // Load master name if possible
        $master = \App\Models\Veterinarian::find($this->masterId);
        if ($master) {
            $this->masterName = $master->full_name;
        }
    }

    public function getCertificationLevelColor(string $level): string
    {
        return match ($level) {
            'basic' => 'bg-gray-100 text-gray-800',
            'certified' => 'bg-blue-100 text-blue-800',
            'advanced' => 'bg-purple-100 text-purple-800',
            'master' => 'bg-yellow-100 text-yellow-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }

    public function getSpecializationLevelColor(string $level): string
    {
        return match ($level) {
            'beginner' => 'bg-gray-100 text-gray-800',
            'intermediate' => 'bg-green-100 text-green-800',
            'advanced' => 'bg-blue-100 text-blue-800',
            'expert' => 'bg-purple-100 text-purple-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }

    public function isCertificationValid(array $data): bool
    {
        if (! $data['certification_level']) {
            return false;
        }

        if (! $data['expiry_date']) {
            return true; // No expiry means valid
        }

        return \Carbon\Carbon::parse($data['expiry_date'])->isFuture();
    }

    public function render()
    {
        return view('livewire.breed-certification-matrix');
    }
}

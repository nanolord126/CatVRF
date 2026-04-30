<?php

declare(strict_types=1);

namespace App\Http\Livewire\Onboarding;

use Illuminate\Contracts\View\Factory as ViewFactory;

use Livewire\Component;
use App\Services\Onboarding\OnboardingService;
use Illuminate\Auth\AuthManager;
use Illuminate\Support\Collection;

/**
 * Branch Registration Form Livewire Component
 * Simplified 30-second flow for branch registration
 */
final class BranchRegisterForm extends Component
{
    public string $parentTenantId = '';

    public string $branchInn = '';

    public string $branchName = '';

    public string $branchAddress = '';

    public string $innError = '';

    public bool $isSubmitting = false;

    public Collection $tenants;

    protected readonly OnboardingService $onboardingService;

    public function __construct(
        private readonly ViewFactory $viewFactory,
    ) {}

    public function mount(OnboardingService $onboardingService): void
    {
        $this->onboardingService = $onboardingService;
        $this->tenants = $this->auth->user()->ownedTenants;
    }

    public function validateInn(): void
    {
        $this->validate([
            'branchInn' => 'required|string|size:10|size:12',
        ]);

        try {
            $result = $this->onboardingService->validateInn($this->branchInn);

            if (! $result['valid']) {
                $this->innError = $result['reason'] ?? 'ИНН недействителен';

                return;
            }

            $this->innError = '';
        } catch (\Throwable $e) {
            $this->innError = 'Ошибка при проверке ИНН';
        }
    }

    public function submitBranch(): void
    {
        $this->validate([
            'parentTenantId' => 'required|string|exists:tenants,id',
            'branchInn' => 'required|string|size:10|size:12',
            'branchName' => 'required|string|max:255',
            'branchAddress' => 'required|string|max:500',
        ]);

        if ($this->innError) {
            return;
        }

        $this->isSubmitting = true;

        try {
            $result = $this->onboardingService->registerBranch(
                ownerUserId: $this->auth->id(),
                parentTenantId: $this->parentTenantId,
                branchInn: $this->branchInn,
                branchName: $this->branchName,
                branchAddress: $this->branchAddress,
                correlationId: '',
            );

            if ($result['success']) {
                session()->flash('success', 'Филиал успешно зарегистрирован');
                $this->reset(['branchInn', 'branchName', 'branchAddress', 'innError']);
            }
        } catch (\Throwable $e) {
            session()->flash('error', 'Ошибка при регистрации филиала: '.$e->getMessage());
        } finally {
            $this->isSubmitting = false;
        }
    }

    public function render()
    {
        return $this->viewFactory->make('livewire.onboarding.branch-register-form');
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Livewire\Auth;

use Illuminate\Contracts\View\Factory as ViewFactory;

use App\Services\Auth\TenantOnboardingService;
use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Str;
use Illuminate\View\View;

final class TenantRegisterForm extends Component
{
    use WithFileUploads;

    public string $name = '';

    public string $inn = '';

    public ?string $kpp = '';

    public ?string $ogrn = '';

    public string $legal_entity_type = 'OOO';

    public ?string $legal_address = '';

    public ?string $actual_address = '';

    public string $phone = '';

    public string $email = '';

    public ?string $website = '';

    public string $timezone = 'Europe/Moscow';

    public bool $loading = false;

    public string $successMessage = '';

    public string $errorMessage = '';

    public function __construct(private readonly ViewFactory $viewFactory,
        private readonly TenantOnboardingService $tenantOnboarding,) {}

    public function registerTenant(): void
    {
        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'inn' => ['required', 'string', 'size:10', 'size:12'],
            'kpp' => ['nullable', 'string', 'size:9'],
            'ogrn' => ['nullable', 'string', 'size:13', 'size:15'],
            'legal_entity_type' => ['required', 'in:OOO,IP,AO'],
            'legal_address' => ['nullable', 'string', 'max:500'],
            'actual_address' => ['nullable', 'string', 'max:500'],
            'phone' => ['required', 'string', 'max:20'],
            'email' => ['required', 'email'],
            'website' => ['nullable', 'url'],
            'timezone' => ['nullable', 'string'],
        ]);

        $this->loading = true;
        $this->errorMessage = '';
        $this->successMessage = '';

        try {
            $tenant = $this->tenantOnboarding->registerTenant([
                'name' => $this->name,
                'inn' => $this->inn,
                'kpp' => $this->kpp,
                'ogrn' => $this->ogrn,
                'legal_entity_type' => $this->legal_entity_type,
                'legal_address' => $this->legal_address,
                'actual_address' => $this->actual_address,
                'phone' => $this->phone,
                'email' => $this->email,
                'website' => $this->website,
                'timezone' => $this->timezone,
                'user_id' => auth()->id(),
                'correlation_id' => Str::uuid()->toString(),
            ]);

            $this->successMessage = 'Tenant registered successfully! Your business is now pending verification.';

            // Reset form
            $this->reset(['name', 'inn', 'kpp', 'ogrn', 'legal_address', 'actual_address', 'phone', 'email', 'website']);

            $this->dispatch('tenant-registration-successful', tenantId: $tenant->id);
        } catch (\Throwable $e) {
            $this->errorMessage = $e->getMessage();
        } finally {
            $this->loading = false;
        }
    }

    public function render(): View
    {
        return $this->viewFactory->make('livewire.auth.tenant-register-form');
    }
}

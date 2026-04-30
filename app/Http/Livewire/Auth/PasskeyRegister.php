<?php

declare(strict_types=1);

namespace App\Http\Livewire\Auth;

use Illuminate\Contracts\View\Factory as ViewFactory;

use Livewire\Component;
use Illuminate\Auth\AuthManager;
use App\Services\Auth\WebAuthn\WebAuthnRegistrationService;

/**
 * Passkey Registration Livewire Component
 *
 * Handles registration of new passkeys for authenticated users.
 * Provides Blade template integration for traditional server-rendered views.
 *
 * PRODUCTION MANDATORY — CatVRF 2026 Enterprise Security
 */
final class PasskeyRegister extends Component
{
    public string $credentialName = '';

    public string $error = '';

    public bool $loading = false;

    public bool $isSupported = false;

    public string $authenticatorAttachment = 'platform';

    public bool $userVerificationRequired = true;

    protected readonly WebAuthnRegistrationService $registrationService;

    public function __construct(
        private readonly ViewFactory $viewFactory,
    ) {}

    public function boot(WebAuthnRegistrationService $registrationService): void
    {
        $this->registrationService = $registrationService;
    }

    public function mount(): void
    {
        $this->isSupported = $this->checkWebAuthnSupport();
    }

    public function render()
    {
        return $this->viewFactory->make('livewire.auth.passkey-register');
    }

    public function initiateRegistration(): void
    {
        if (! $this->auth->check()) {
            $this->error = 'You must be logged in to register a passkey.';

            return;
        }

        $this->loading = true;
        $this->error = '';

        try {
            $user = $this->auth->user();

            // Step 1: Get registration options
            $result = $this->registrationService->generateRegistrationOptions(
                user: $user,
                authenticatorAttachment: $this->authenticatorAttachment,
                requireUserVerification: $this->userVerificationRequired,
            );

            // Pass options to frontend for WebAuthn API call
            $this->dispatch('passkey-register-initiate', [
                'options' => $result['options'],
                'challenge_id' => $result['challenge_id'],
            ]);
        } catch (\Exception $e) {
            $this->error = $e->getMessage();
            $this->loading = false;
        }
    }

    public function completeRegistration(array $attestation, string $challengeId): void
    {
        if (! $this->auth->check()) {
            $this->error = 'You must be logged in to register a passkey.';
            $this->loading = false;

            return;
        }

        try {
            $user = $this->auth->user();

            $credential = $this->registrationService->registerCredential(
                user: $user,
                challengeId: $challengeId,
                attestation: $attestation,
                credentialName: $this->credentialName ?: null,
            );

            $this->loading = false;
            $this->credentialName = '';

            $this->dispatch('passkey-registered', [
                'credential' => $credential,
            ]);

            session()->flash('success', 'Passkey registered successfully!');
        } catch (\Exception $e) {
            $this->error = $e->getMessage();
            $this->loading = false;
        }
    }

    private function checkWebAuthnSupport(): bool
    {
        return true;
    }
}

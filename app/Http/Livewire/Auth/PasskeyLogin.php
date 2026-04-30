<?php

declare(strict_types=1);

namespace App\Http\Livewire\Auth;

use Illuminate\Routing\Redirector;

use Illuminate\Contracts\View\Factory as ViewFactory;

use Livewire\Component;
use Illuminate\Auth\AuthManager;
use App\Services\Auth\WebAuthn\WebAuthnAuthenticationService;

/**
 * Passkey Login Livewire Component
 *
 * Handles passwordless authentication using WebAuthn/Passkeys.
 * Provides Blade template integration for traditional server-rendered views.
 *
 * PRODUCTION MANDATORY — CatVRF 2026 Enterprise Security
 */
final class PasskeyLogin extends Component
{
    public string $email = '';

    public string $error = '';

    public bool $loading = false;

    public bool $isSupported = false;

    public ?string $redirectUrl = null;

    protected readonly WebAuthnAuthenticationService $authService;

    public function __construct(private readonly Redirector $redirector,
        private readonly ViewFactory $viewFactory,) {}

    public function boot(WebAuthnAuthenticationService $authService): void
    {
        $this->authService = $authService;
    }

    public function mount(?string $redirect = null): void
    {
        $this->redirectUrl = $redirect;
        $this->isSupported = $this->checkWebAuthnSupport();
    }

    public function render()
    {
        return $this->viewFactory->make('livewire.auth.passkey-login');
    }

    public function initiateLogin(): void
    {
        $this->validate([
            'email' => 'required|email',
        ]);

        $this->loading = true;
        $this->error = '';

        try {
            // Step 1: Get authentication options
            $result = $this->authService->generateAuthenticationOptions($this->email);

            if (! $result['user_found'] || ! $result['has_credentials']) {
                $this->error = 'No passkeys registered for this account. Please register a passkey first.';
                $this->loading = false;

                return;
            }

            // Pass options to frontend for WebAuthn API call
            $this->dispatch('passkey-login-initiate', [
                'options' => $result['options'],
                'challenge_id' => $result['challenge_id'],
            ]);
        } catch (\Exception $e) {
            $this->error = $e->getMessage();
            $this->loading = false;
        }
    }

    public function completeLogin(array $assertion, string $challengeId): mixed
    {
        try {
            $result = $this->authService->authenticate($challengeId, $assertion);

            // Log user in
            $this->auth->login($result['user']);

            // Redirect
            if ($this->redirectUrl) {
                return $this->redirector->to()->to($this->redirectUrl);
            }

            return $this->redirector->to()->intended('/dashboard');
        } catch (\Exception $e) {
            $this->error = $e->getMessage();
            $this->loading = false;

            return null;
        }
    }

    private function checkWebAuthnSupport(): bool
    {
        // This is a server-side check
        // Actual browser support is checked on the client side
        return true;
    }
}

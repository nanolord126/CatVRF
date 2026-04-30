<?php

declare(strict_types=1);

namespace App\Http\Livewire\Auth;

use Illuminate\Contracts\View\Factory as ViewFactory;

use Illuminate\Support\Collection;

use Livewire\Component;
use Illuminate\Auth\AuthManager;
use App\Services\Auth\WebAuthn\WebAuthnCredentialService;
use Carbon\Carbon;

/**
 * Passkey Manager Livewire Component
 *
 * Manages user's registered passkeys (list, rename, delete).
 * Provides Blade template integration for traditional server-rendered views.
 *
 * PRODUCTION MANDATORY — CatVRF 2026 Enterprise Security
 */
final class PasskeyManager extends Component
{
    public string $error = '';

    public bool $loading = false;

    public $credentials = [];

    public $stats = [];

    // Editing state
    public ?int $editingCredentialId = null;

    public string $newName = '';

    // Deleting state
    public ?int $deletingCredentialId = null;

    protected readonly WebAuthnCredentialService $credentialService;

    public function __construct(
        private readonly ViewFactory $viewFactory,
    ) {}

    public function boot(WebAuthnCredentialService $credentialService): void
    {
        $this->credentialService = $credentialService;
    }

    public function mount(): void
    {
        $this->loadCredentials();
    }

    public function render()
    {
        return $this->viewFactory->make('livewire.auth.passkey-manager');
    }

    public function loadCredentials(): void
    {
        if (! $this->auth->check()) {
            return;
        }

        $this->loading = true;
        $this->error = '';

        try {
            $user = $this->auth->user();

            $this->credentials = $this->credentialService->getUserCredentials($user);
            $this->stats = $this->credentialService->getCredentialStats($user);
        } catch (\Exception $e) {
            $this->error = $e->getMessage();
        } finally {
            $this->loading = false;
        }
    }

    public function startEdit(int $credentialId): void
    {
        $credential = new Collection($this->credentials)->firstWhere('id', $credentialId);

        if ($credential) {
            $this->editingCredentialId = $credentialId;
            $this->newName = $credential['name'];
        }
    }

    public function cancelEdit(): void
    {
        $this->editingCredentialId = null;
        $this->newName = '';
    }

    public function renameCredential(): void
    {
        $this->validate([
            'newName' => 'required|string|max:255',
        ]);

        if (! $this->auth->check()) {
            return;
        }

        $this->loading = true;
        $this->error = '';

        try {
            $user = $this->auth->user();

            $this->credentialService->renameCredential(
                user: $user,
                credentialId: $this->editingCredentialId,
                name: $this->newName,
            );

            // Update local state
            $index = new Collection($this->credentials)->search(fn ($c) => $c['id'] === $this->editingCredentialId);
            if ($index !== false) {
                $this->credentials[$index]['name'] = $this->newName;
            }

            $this->editingCredentialId = null;
            $this->newName = '';

            session()->flash('success', 'Passkey renamed successfully!');
        } catch (\Exception $e) {
            $this->error = $e->getMessage();
        } finally {
            $this->loading = false;
        }
    }

    public function confirmDelete(int $credentialId): void
    {
        $this->deletingCredentialId = $credentialId;
    }

    public function cancelDelete(): void
    {
        $this->deletingCredentialId = null;
    }

    public function deleteCredential(): void
    {
        if (! $this->auth->check()) {
            return;
        }

        $this->loading = true;
        $this->error = '';

        try {
            $user = $this->auth->user();

            $this->credentialService->deleteCredential(
                user: $user,
                credentialId: $this->deletingCredentialId,
                revokeSessions: true,
            );

            // Reload credentials
            $this->loadCredentials();

            $this->deletingCredentialId = null;

            session()->flash('success', 'Passkey deleted successfully!');
        } catch (\Exception $e) {
            $this->error = $e->getMessage();
        } finally {
            $this->loading = false;
        }
    }

    public function getDeviceIconProperty($credential): string
    {
        $userAgent = $credential['user_agent'] ?? '';

        if (str_contains($userAgent, 'iPhone') || str_contains($userAgent, 'iPad')) {
            return '🍎';
        }
        if (str_contains($userAgent, 'Mac')) {
            return '🍎';
        }
        if (str_contains($userAgent, 'Windows')) {
            return '🪟';
        }
        if (str_contains($userAgent, 'Android')) {
            return '🤖';
        }

        return '🔑';
    }

    public function formatDateProperty($date): string
    {
        if (! $date) {
            return 'Never';
        }

        return Carbon::parse($date)->diffForHumans();
    }
}

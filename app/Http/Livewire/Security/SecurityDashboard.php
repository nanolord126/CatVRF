<?php

declare(strict_types=1);

namespace App\Http\Livewire\Security;

use Illuminate\Contracts\View\Factory as ViewFactory;

use App\Services\Security\AccountProtectionService;
use Illuminate\Auth\AuthManager;
use Livewire\Component;

final class SecurityDashboard extends Component
{
    public $securityStatus;

    public $accountLocked;

    public $fraudScore;

    public $passkeyCount;

    public $deviceCount;

    public $faceVerified;

    public $securityLevel;

    public function __construct(
        private readonly ViewFactory $viewFactory,
    ) {}

    public function mount(AccountProtectionService $accountProtection)
    {
        $user = $this->auth->user();

        $this->accountLocked = $accountProtection->isAccountLocked($user);
        $this->fraudScore = $accountProtection->getUserFraudScore($user->id);
        $this->passkeyCount = $user->webauthnCredentials()->where('is_compromised', false)->count();
        $this->deviceCount = $user->devices()->where('is_revoked', false)->count();
        $this->faceVerified = ! is_null($user->face_verified_at);

        $this->securityLevel = $this->calculateSecurityLevel();
    }

    public function getSecurityLevelColorProperty(): string
    {
        return match ($this->securityLevel) {
            'high' => 'green',
            'medium' => 'yellow',
            'low' => 'red',
            'critical' => 'red',
            default => 'gray',
        };
    }

    public function render()
    {
        return $this->viewFactory->make('livewire.security.security-dashboard');
    }

    private function calculateSecurityLevel(): string
    {
        if ($this->fraudScore >= 0.70) {
            return 'critical';
        }

        if ($this->passkeyCount === 0) {
            return 'low';
        }

        if ($this->passkeyCount >= 2 && $this->deviceCount >= 1 && $this->fraudScore < 0.30) {
            return 'high';
        }

        if ($this->passkeyCount >= 1 && $this->fraudScore < 0.50) {
            return 'medium';
        }

        return 'low';
    }
}

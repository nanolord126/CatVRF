<?php

declare(strict_types=1);

namespace App\Http\Livewire\Security;

use Illuminate\Contracts\View\Factory as ViewFactory;

use App\Models\AccountRecoveryLog;
use App\Services\Security\RecoveryService;
use Livewire\Component;
use App\Models\User;

final class RecoveryFlow extends Component
{
    public string $email = '';

    public string $method = 'email';

    public string $verificationCode = '';

    public string $recoveryLogId = '';

    public string $status = 'init'; // init, verification, complete, success, error

    public string $errorMessage = '';

    public function __construct(
        private readonly ViewFactory $viewFactory,
    ) {}

    public function rules()
    {
        return [
            'email' => 'required|email|exists:users,email',
            'method' => 'required|in:email,sms,backup_code,ai_face',
            'verificationCode' => 'required_if:status,verification|string',
        ];
    }

    public function initRecovery(RecoveryService $recoveryService)
    {
        $this->validate([
            'email' => 'required|email|exists:users,email',
            'method' => 'required|in:email,sms,backup_code,ai_face',
        ]);

        try {
            $user = User::where('email', $this->email)->firstOrFail();

            $recoveryLog = $recoveryService->initRecovery(
                $user,
                $this->method,
                request()->ip(),
                request()->userAgent(),
                request()->header('X-Device-Fingerprint') ?? request()->ip(),
            );

            $this->recoveryLogId = $recoveryLog->id;
            $this->status = 'verification';

            if ($recoveryLog->isHighRisk()) {
                $this->errorMessage = 'High risk detected. Please contact support for assistance.';
                $this->status = 'error';
            }
        } catch (\Exception $e) {
            $this->errorMessage = $e->getMessage();
            $this->status = 'error';
        }
    }

    public function verifyRecovery(RecoveryService $recoveryService)
    {
        $this->validate(['verificationCode' => 'required|string']);

        try {
            $recoveryLog = AccountRecoveryLog::where('id', $this->recoveryLogId)
                ->where('status', AccountRecoveryLog::STATUS_INITIATED)
                ->firstOrFail();

            $verified = $recoveryService->verifyStep(
                $recoveryLog,
                $this->verificationCode,
            );

            if (! $verified) {
                $this->errorMessage = 'Invalid verification code. Please try again.';

                return;
            }

            $this->status = 'complete';
        } catch (\Exception $e) {
            $this->errorMessage = $e->getMessage();
            $this->status = 'error';
        }
    }

    public function reset()
    {
        $this->email = '';
        $this->method = 'email';
        $this->verificationCode = '';
        $this->recoveryLogId = '';
        $this->status = 'init';
        $this->errorMessage = '';
    }

    public function render()
    {
        return $this->viewFactory->make('livewire.security.recovery-flow');
    }
}

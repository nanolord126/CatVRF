<?php

declare(strict_types=1);

namespace App\Http\Livewire\Onboarding;

use Illuminate\Contracts\View\Factory as ViewFactory;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Services\Onboarding\OnboardingService;
use Illuminate\Auth\AuthManager;

/**
 * Photo + FIO Verification Step Livewire Component
 * Identity verification with liveness detection
 */
final class PhotoFioVerificationStep extends Component
{
    use WithFileUploads;

    public string $firstName = '';

    public string $lastName = '';

    public string $middleName = '';

    public $photo;

    public $passportPhoto;

    public bool $consentGiven = false;

    public bool $isProcessing = false;

    public array $verificationResult = [];

    public string $error = '';

    protected readonly OnboardingService $onboardingService;

    public function __construct(
        private readonly ViewFactory $viewFactory,
    ) {}

    public function boot(OnboardingService $onboardingService): void
    {
        $this->onboardingService = $onboardingService;
    }

    public function giveConsent(): void
    {
        try {
            $this->onboardingService->giveConsent($this->auth->id());
            $this->consentGiven = true;
        } catch (\Throwable $e) {
            $this->error = 'Ошибка при сохранении согласия';
        }
    }

    public function submitVerification(): void
    {
        $this->validate([
            'firstName' => 'required|string',
            'lastName' => 'required|string',
            'middleName' => 'nullable|string',
            'photo' => 'required|file|image|max:5120',
            'passportPhoto' => 'nullable|file|image|max:5120',
        ]);

        if (! $this->consentGiven) {
            $this->error = 'Требуется согласие на обработку персональных данных';

            return;
        }

        $this->isProcessing = true;

        try {
            $result = $this->onboardingService->verifyIdentity(
                userId: $this->auth->id(),
                firstName: $this->firstName,
                lastName: $this->lastName,
                middleName: $this->middleName,
                photo: $this->photo,
                passportPhoto: $this->passportPhoto,
                correlationId: '',
            );

            $this->verificationResult = $result;

            if ($result['success']) {
                session()->flash('success', 'Верификация пройдена успешно');
            } elseif ($result['result'] === 'requires_review') {
                session()->flash('warning', 'Верификация требует ручной проверки');
            } else {
                session()->flash('error', $result['reason'] ?? 'Верификация не пройдена');
            }
        } catch (\Throwable $e) {
            $this->error = 'Ошибка при верификации: '.$e->getMessage();
        } finally {
            $this->isProcessing = false;
        }
    }

    public function render()
    {
        return $this->viewFactory->make('livewire.onboarding.photo-fio-verification-step');
    }
}

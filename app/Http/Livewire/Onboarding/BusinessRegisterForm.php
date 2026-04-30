<?php

declare(strict_types=1);

namespace App\Http\Livewire\Onboarding;

use Illuminate\Contracts\View\Factory as ViewFactory;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Services\Onboarding\OnboardingService;
use Illuminate\Auth\AuthManager;

/**
 * Business Registration Form Livewire Component
 * Multi-step form for complete business registration with document upload
 */
final class BusinessRegisterForm extends Component
{
    use WithFileUploads;

    public string $step = 'inn'; // inn, documents, review

    // Step 1: INN
    public string $inn = '';

    public array $companyData = [];

    public string $innError = '';

    // Step 2: Documents
    public $egrulDocument;

    public $passportDocument;

    public $selfiePhoto;

    public array $uploadedDocuments = [];

    // Progress
    public int $progress = 0;

    protected readonly OnboardingService $onboardingService;

    public function __construct(
        private readonly ViewFactory $viewFactory,
    ) {}

    public function boot(OnboardingService $onboardingService): void
    {
        $this->onboardingService = $onboardingService;
    }

    public function validateInn(): void
    {
        $this->validate([
            'inn' => 'required|string|size:10|size:12',
        ]);

        try {
            $result = $this->onboardingService->validateInn($this->inn);

            if (! $result['valid']) {
                $this->innError = $result['reason'] ?? 'ИНН недействителен';

                return;
            }

            $this->companyData = $result;
            $this->innError = '';
            $this->step = 'documents';
            $this->progress = 33;
        } catch (\Throwable $e) {
            $this->innError = 'Ошибка при проверке ИНН';
        }
    }

    public function uploadDocuments(): void
    {
        $this->validate([
            'egrulDocument' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'passportDocument' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'selfiePhoto' => 'required|file|image|max:5120',
        ]);

        try {
            $documents = [
                'egrul' => $this->egrulDocument,
            ];

            if ($this->passportDocument) {
                $documents['passport'] = $this->passportDocument;
            }

            $result = $this->onboardingService->uploadDocuments(
                userId: $this->auth->id(),
                documents: $documents,
                expectedInn: $this->inn,
                correlationId: '',
            );

            $this->uploadedDocuments = $result['documents'];

            // Store selfie
            if ($this->selfiePhoto) {
                $this->selfiePhoto->storeAs('selfies/'.$this->auth->id(), $this->selfiePhoto->getClientOriginalName(), 'secure');
            }

            $this->step = 'review';
            $this->progress = 66;
        } catch (\Throwable $e) {
            $this->addError('upload', 'Ошибка при загрузке документов');
        }
    }

    public function submitRegistration(): void
    {
        try {
            $result = $this->onboardingService->registerBusiness(
                ownerUserId: $this->auth->id(),
                inn: $this->inn,
                documents: $this->uploadedDocuments,
                selfiePhotoPath: $this->selfiePhoto ? 'selfies/'.$this->auth->id().'/'.$this->selfiePhoto->getClientOriginalName() : null,
                correlationId: '',
            );

            $this->progress = 100;

            session()->flash('success', 'Регистрация бизнеса отправлена на модерацию');
            $this->step = 'complete';
        } catch (\Throwable $e) {
            $this->addError('submit', 'Ошибка при регистрации: '.$e->getMessage());
        }
    }

    public function render()
    {
        return $this->viewFactory->make('livewire.onboarding.business-register-form');
    }
}

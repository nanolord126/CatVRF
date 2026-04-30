<?php

declare(strict_types=1);

namespace App\Services\Onboarding;

use Psr\Log\LoggerInterface;

use App\Models\Tenant;
use App\Models\User;
use App\Models\VerificationLog;
use App\Services\FraudControlService;
use Illuminate\Http\UploadedFile;
use Illuminate\Log\LogManager;

/**
 * Unified Onboarding Service for API, Livewire, and Filament.
 * Orchestrates all onboarding flows with fraud control and audit logging.
 * Production-ready with 152-ФZ compliance.
 */
final readonly class OnboardingService
{
    public function __construct(private readonly LoggerInterface $logger,
        private readonly BusinessRegistrationService $businessRegistration,
        private readonly DaDataService $daDataService,
        private readonly AIIdentityService $aiIdentityService,
        private readonly DocumentVerificationService $documentService,
        private readonly FraudControlService $fraudControl,
        private readonly LogManager $log,) {}

    /**
     * Step 1: Validate INN (for business registration)
     */
    public function validateInn(string $inn, string $correlationId = ''): array
    {
        return $this->daDataService->validateInn($inn);
    }

    /**
     * Step 2: Suggest companies by name (autocomplete)
     */
    public function suggestCompanies(string $name, int $count = 5): array
    {
        return $this->daDataService->suggestByName($name, $count);
    }

    /**
     * Step 3: Upload and verify documents (EGRUL, passport)
     */
    public function uploadDocuments(
        int $userId,
        array $documents, // ['egrul' => UploadedFile, 'passport' => UploadedFile]
        string $expectedInn,
        string $correlationId = ''
    ): array {
        // Fraud check
        $this->fraudControl->check(
            userId: $userId,
            operationType: 'document_upload',
            amount: 0,
            correlationId: $correlationId,
        );

        $results = [];

        foreach ($documents as $type => $file) {
            $path = $this->documentService->storeDocument($file, "documents/{$userId}");

            $result = match ($type) {
                'egrul' => $this->documentService->verifyEgrul($file, $expectedInn),
                'passport' => $this->documentService->verifyPassport($file),
                default => ['success' => false, 'score' => 0.0, 'reason' => 'Unknown document type'],
            };

            $results[$type] = [
                'path' => $path,
                'verification' => $result,
            ];

            $this->documentService->logVerification(
                userId: $userId,
                tenantId: null,
                businessGroupId: null,
                documentType: $type,
                result: $result,
                correlationId: $correlationId,
            );
        }

        return [
            'success' => true,
            'documents' => $results,
        ];
    }

    /**
     * Step 4: Verify FIO + photo (identity verification)
     */
    public function verifyIdentity(
        int $userId,
        string $firstName,
        string $lastName,
        ?string $middleName,
        UploadedFile $photo,
        ?UploadedFile $passportPhoto = null,
        string $correlationId = ''
    ): array {
        // Fraud check
        $this->fraudControl->check(
            userId: $userId,
            operationType: 'identity_verification',
            amount: 0,
            correlationId: $correlationId,
        );

        // Check consent
        $user = User::findOrFail($userId);
        if (! $user->hasGivenConsent()) {
            throw new \DomainException('Требуется согласие на обработку персональных данных');
        }

        // Validate with AI
        $result = $this->aiIdentityService->validatePhotoFio(
            userId: $userId,
            firstName: $firstName,
            lastName: $lastName,
            middleName: $middleName,
            photo: $photo,
            passportPhoto: $passportPhoto,
            correlationId: $correlationId,
        );

        // Log verification
        $this->aiIdentityService->logVerification(
            userId: $userId,
            tenantId: null,
            businessGroupId: null,
            result: $result,
            correlationId: $correlationId,
        );

        // Update user status
        if ($result['success']) {
            $user->markAsVerified($result['score']);
        } elseif ($result['result'] === 'requires_review') {
            $user->markAsPending();
        } else {
            $user->markAsRejected($result['reason'] ?? 'Verification failed');
        }

        return $result;
    }

    /**
     * Step 5: Register full business (orchestrator)
     */
    public function registerBusiness(
        int $ownerUserId,
        string $inn,
        array $documents,
        ?string $selfiePhotoPath = null,
        string $correlationId = ''
    ): array {
        return $this->businessRegistration->registerFullBusiness(
            ownerUserId: $ownerUserId,
            inn: $inn,
            documents: $documents,
            selfiePhotoPath: $selfiePhotoPath,
            correlationId: $correlationId,
        );
    }

    /**
     * Step 6: Register branch (orchestrator)
     */
    public function registerBranch(
        int $ownerUserId,
        int $parentTenantId,
        string $branchInn,
        string $branchName,
        string $branchAddress,
        string $correlationId = ''
    ): array {
        return $this->businessRegistration->registerBranch(
            ownerUserId: $ownerUserId,
            parentTenantId: $parentTenantId,
            branchInn: $branchInn,
            branchName: $branchName,
            branchAddress: $branchAddress,
            correlationId: $correlationId,
        );
    }

    /**
     * Give consent for data processing (152-ФZ compliance)
     */
    public function giveConsent(int $userId): array
    {
        $user = User::findOrFail($userId);
        $user->giveConsent();

        $this->log->channel('audit')->$this->logger->info('User gave consent for data processing', [
            'user_id' => $userId,
        ]);

        return [
            'success' => true,
            'consent_given_at' => $user->consent_given_at,
        ];
    }

    /**
     * Get onboarding status for user
     */
    public function getOnboardingStatus(int $userId): array
    {
        $user = User::findOrFail($userId);

        $verifications = VerificationLog::byUser($userId)
            ->orderBy('created_at', 'desc')
            ->get()
            ->groupBy('type');

        return [
            'user_id' => $userId,
            'verification_status' => $user->verification_status->value,
            'is_verified' => $user->isVerified(),
            'verification_score' => $user->verification_score,
            'consent_given' => $user->hasGivenConsent(),
            'verifications' => $verifications->map(fn ($logs) => $logs->first())->toArray(),
        ];
    }

    /**
     * Get tenant verification status
     */
    public function getTenantVerificationStatus(string $tenantId): array
    {
        $tenant = Tenant::findOrFail($tenantId);

        $verifications = VerificationLog::byTenant($tenantId)
            ->orderBy('created_at', 'desc')
            ->get()
            ->groupBy('type');

        return [
            'tenant_id' => $tenantId,
            'verification_status' => $tenant->verification_status->value,
            'is_verified' => $tenant->is_verified,
            'is_active' => $tenant->is_active,
            'can_operate' => $tenant->canOperate(),
            'verifications' => $verifications->map(fn ($logs) => $logs->first())->toArray(),
        ];
    }
}

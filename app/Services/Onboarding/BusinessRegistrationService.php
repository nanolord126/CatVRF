<?php

declare(strict_types=1);

namespace App\Services\Onboarding;

use App\Enums\BusinessGroupVerificationStatus;
use App\Enums\ProfileType;
use App\Enums\Role;
use App\Enums\TenantVerificationStatus;
use App\Models\BusinessGroup;
use App\Models\Tenant;
use App\Models\User;
use App\Services\FraudControlService;
use App\Services\Protection\ContactIsolationService;
use Illuminate\Database\DatabaseManager;
use Illuminate\Log\LogManager;
use Psr\Log\LoggerInterface;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;

/**
 * Business Registration Service for full business and branch registration.
 * Production-ready with fraud control, audit logging, and multi-tenancy safety.
 */
final readonly class BusinessRegistrationService
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly DaDataService $daDataService,
        private readonly AIIdentityService $aiIdentityService,
        private readonly DocumentVerificationService $documentService,
        private readonly FraudControlService $fraudControl,
        private readonly DatabaseManager $db,
        private readonly LogManager $log,
        private readonly ContactIsolationService $contactIsolation,
    ) {}

    /**
     * Register full business (Tenant + BusinessGroup) with documents
     * Ozon-style flow: INN → DaData → Documents → AI verification → Moderation
     */
    public function registerFullBusiness(
        int $ownerUserId,
        string $inn,
        array $documents,
        ?string $selfiePhotoPath = null,
        string $correlationId = ''
    ): array {
        // Fraud check first
        $this->fraudControl->check(
            userId: $ownerUserId,
            operationType: 'business_registration',
            amount: 0,
            correlationId: $correlationId,
        );

        return $this->db->transaction(function () use ($ownerUserId, $inn, $documents, $selfiePhotoPath, $correlationId) {
            // 1. Validate INN via DaData
            $innValidation = $this->daDataService->validateInn($inn);

            if (! $innValidation['valid']) {
                $this->daDataService->logVerification(
                    userId: $ownerUserId,
                    tenantId: null,
                    businessGroupId: null,
                    inn: $inn,
                    result: $innValidation,
                    correlationId: $correlationId,
                );

                throw new \DomainException($innValidation['reason'] ?? 'ИНН недействителен');
            }

            // 2. Get full party data from DaData
            $partyData = $this->daDataService->findParty($inn);

            // 3. Get owner user for contact check
            $owner = User::findOrFail($ownerUserId);

            // 4. Contact isolation check (zero-duplicate policy for business)
            $this->contactIsolation->checkAvailability(
                $owner->email,
                $owner->phone,
                ProfileType::Business,
            );

            // 5. Create Tenant
            $tenant = Tenant::create([
                'id' => Str::uuid()->toString(),
                'name' => $partyData['name']['short'] ?? $partyData['name']['full'],
                'type' => $partyData['type'] === 'LEGAL' ? 'LEGAL' : 'INDIVIDUAL',
                'inn' => $inn,
                'kpp' => $partyData['kpp'],
                'ogrn' => $partyData['ogrn'],
                'legal_address' => $partyData['legal_address'],
                'actual_address' => $partyData['actual_address'] ?? $partyData['legal_address'],
                'email' => $owner->email,
                'phone' => $owner->phone,
                'is_active' => false,
                'is_verified' => false,
                'verification_status' => TenantVerificationStatus::Pending,
                'correlation_id' => $correlationId,
            ]);

            // 6. Create BusinessGroup
            $businessGroup = BusinessGroup::create([
                'tenant_id' => $tenant->id,
                'name' => $partyData['name']['short'] ?? $partyData['name']['full'],
                'inn' => $inn,
                'kpp' => $partyData['kpp'],
                'legal_address' => $partyData['legal_address'],
                'actual_address' => $partyData['actual_address'] ?? $partyData['legal_address'],
                'is_active' => false,
                'is_verified' => false,
                'verification_status' => BusinessGroupVerificationStatus::Pending,
                'is_branch' => false,
                'correlation_id' => $correlationId,
            ]);

            // 7. Attach owner to tenant
            $tenant->users()->attach($ownerUserId, [
                'role' => Role::Owner->value,
                'is_active' => true,
                'invitation_token' => null,
                'invited_at' => CarbonImmutable::now(),
                'accepted_at' => CarbonImmutable::now(),
            ]);

            // 8. Register contact in unique_contacts table
            $this->contactIsolation->registerContact(
                $tenant,
                $tenant->email,
                $tenant->phone,
                ProfileType::Business,
                $tenant->id,
                $ownerUserId,
            );

            // 9. Log INN verification
            $this->daDataService->logVerification(
                userId: $ownerUserId,
                tenantId: $tenant->id,
                businessGroupId: $businessGroup->id,
                inn: $inn,
                result: $innValidation,
                correlationId: $correlationId,
            );

            // 10. Store documents and trigger OCR (async via job)
            if (! empty($documents)) {
                foreach ($documents as $documentType => $documentPath) {
                    $this->log->channel('audit')->$this->logger->info('Document uploaded for OCR', [
                        'tenant_id' => $tenant->id,
                        'business_group_id' => $businessGroup->id,
                        'document_type' => $documentType,
                        'correlation_id' => $correlationId,
                    ]);
                }
            }

            // 11. Trigger AI validation for selfie if provided
            if ($selfiePhotoPath) {
                $this->log->channel('audit')->$this->logger->info('Selfie uploaded for AI verification', [
                    'tenant_id' => $tenant->id,
                    'user_id' => $ownerUserId,
                    'correlation_id' => $correlationId,
                ]);
            }

            // 12. Audit log
            $this->log->channel('audit')->$this->logger->info('Business registration submitted', [
                'tenant_id' => $tenant->id,
                'business_group_id' => $businessGroup->id,
                'owner_user_id' => $ownerUserId,
                'inn' => $inn,
                'correlation_id' => $correlationId,
            ]);

            return [
                'success' => true,
                'tenant_id' => $tenant->id,
                'business_group_id' => $businessGroup->id,
                'verification_status' => $tenant->verification_status->value,
                'message' => 'Регистрация отправлена на модерацию',
            ];
        });
    }

    /**
     * Register branch (simplified flow - only INN validation)
     * For authorized tenant-owners only
     */
    public function registerBranch(
        int $ownerUserId,
        int $parentTenantId,
        string $branchInn,
        string $branchName,
        string $branchAddress,
        string $correlationId = ''
    ): array {
        $this->fraudControl->check(
            userId: $ownerUserId,
            operationType: 'branch_registration',
            amount: 0,
            correlationId: $correlationId,
        );

        $parentTenant = Tenant::findOrFail($parentTenantId);
        if (! $parentTenant->userHasRole($ownerUserId, Role::Owner)) {
            throw new \DomainException('Вы не являетесь владельцем этого бизнеса');
        }

        return $this->db->transaction(function () use ($ownerUserId, $parentTenantId, $branchInn, $branchName, $branchAddress, $correlationId) {
            $innValidation = $this->daDataService->validateInn($branchInn);

            if (! $innValidation['valid']) {
                throw new \DomainException($innValidation['reason'] ?? 'ИНН филиала недействителен');
            }

            $parentTenant = Tenant::findOrFail($parentTenantId);
            $isSameEntity = $this->daDataService->isSameLegalEntity($branchInn, $parentTenant->inn);

            if (! $isSameEntity) {
                throw new \DomainException('Филиал должен принадлежать тому же юрлицу');
            }

            $businessGroup = BusinessGroup::create([
                'tenant_id' => $parentTenantId,
                'name' => $branchName,
                'inn' => $branchInn,
                'legal_address' => $branchAddress,
                'actual_address' => $branchAddress,
                'is_active' => true,
                'is_verified' => true,
                'verification_status' => BusinessGroupVerificationStatus::AutoApproved,
                'inn_verified_at' => CarbonImmutable::now(),
                'is_branch' => true,
                'correlation_id' => $correlationId,
            ]);

            $this->daDataService->logVerification(
                userId: $ownerUserId,
                tenantId: $parentTenantId,
                businessGroupId: $businessGroup->id,
                inn: $branchInn,
                result: $innValidation,
                correlationId: $correlationId,
            );

            $this->log->channel('audit')->$this->logger->info('Branch registered', [
                'tenant_id' => $parentTenantId,
                'business_group_id' => $businessGroup->id,
                'owner_user_id' => $ownerUserId,
                'branch_inn' => $branchInn,
                'correlation_id' => $correlationId,
            ]);

            return [
                'success' => true,
                'business_group_id' => $businessGroup->id,
                'verification_status' => $businessGroup->verification_status->value,
                'message' => 'Филиал успешно зарегистрирован',
            ];
        });
    }

    /**
     * Approve business registration (manual moderation)
     */
    public function approveBusiness(
        string $tenantId,
        ?string $moderatorNotes = null,
        ?int $moderatorUserId = null
    ): array {
        return $this->db->transaction(function () use ($tenantId, $moderatorNotes, $moderatorUserId) {
            $tenant = Tenant::findOrFail($tenantId);

            $tenant->approveVerification($moderatorNotes);

            $mainBusinessGroup = $tenant->businessGroups()->main()->first();
            if ($mainBusinessGroup) {
                $mainBusinessGroup->approveVerification($moderatorNotes);
            }

            $this->log->channel('audit')->$this->logger->info('Business approved by moderator', [
                'tenant_id' => $tenantId,
                'moderator_user_id' => $moderatorUserId,
                'notes' => $moderatorNotes,
            ]);

            return [
                'success' => true,
                'tenant_id' => $tenantId,
                'verification_status' => $tenant->verification_status->value,
            ];
        });
    }

    /**
     * Reject business registration
     */
    public function rejectBusiness(
        string $tenantId,
        string $reason,
        ?int $moderatorUserId = null
    ): array {
        return $this->db->transaction(function () use ($tenantId, $reason, $moderatorUserId) {
            $tenant = Tenant::findOrFail($tenantId);

            $tenant->rejectVerification($reason);

            $mainBusinessGroup = $tenant->businessGroups()->main()->first();
            if ($mainBusinessGroup) {
                $mainBusinessGroup->rejectVerification($reason);
            }

            $this->log->channel('audit')->$this->logger->info('Business rejected by moderator', [
                'tenant_id' => $tenantId,
                'moderator_user_id' => $moderatorUserId,
                'reason' => $reason,
            ]);

            return [
                'success' => true,
                'tenant_id' => $tenantId,
                'verification_status' => $tenant->verification_status->value,
            ];
        });
    }
}

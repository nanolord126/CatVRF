<?php

declare(strict_types=1);

namespace App\Services\Auth;

use Illuminate\Contracts\Bus\Dispatcher as BusDispatcher;

use Illuminate\Contracts\Events\Dispatcher as EventDispatcher;

use Psr\Log\LoggerInterface;

use App\Enums\Role;
use App\Enums\TenantVerificationStatus;
use App\Events\Auth\TenantCreated;
use App\Events\Auth\TenantVerified;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Database\DatabaseManager;
use Illuminate\Log\LogManager;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class TenantOnboardingService
{
    public function __construct(private readonly BusDispatcher $bus,
        private readonly EventDispatcher $eventDispatcher,
        private readonly LoggerInterface $logger,
        private readonly FraudControlService $fraudControl,
        private readonly DatabaseManager $db,
        private readonly LogManager $log,) {}

    /**
     * Register new tenant (business)
     */
    public function registerTenant(array $data): Tenant
    {
        return $this->db->transaction(function () use ($data) {
            $this->fraudControl->check(
                $data['user_id'] ?? 0,
                'tenant_registration',
                0,
                request()->ip(),
                null,
                $data['correlation_id'] ?? Str::uuid()->toString(),
            );

            // Validate INN via external service (DaData / FNS)
            $innValidation = $this->validateInn($data['inn']);

            if (! $innValidation['valid']) {
                throw ValidationException::withMessages([
                    'inn' => ['Invalid INN or company not found'],
                ]);
            }

            $tenant = Tenant::create([
                'id' => Str::uuid()->toString(),
                'name' => $data['name'],
                'type' => $data['type'] ?? 'business',
                'slug' => Str::slug($data['name']),
                'inn' => $data['inn'],
                'kpp' => $data['kpp'] ?? null,
                'ogrn' => $data['ogrn'] ?? null,
                'legal_entity_type' => $data['legal_entity_type'] ?? 'OOO',
                'legal_address' => $data['legal_address'] ?? null,
                'actual_address' => $data['actual_address'] ?? null,
                'phone' => $data['phone'],
                'email' => $data['email'],
                'website' => $data['website'] ?? null,
                'is_active' => false, // Inactive until verified
                'is_verified' => false,
                'verification_status' => TenantVerificationStatus::Pending,
                'timezone' => $data['timezone'] ?? 'Europe/Moscow',
                'correlation_id' => $data['correlation_id'] ?? null,
                'meta' => [
                    'dadata_data' => $innValidation['data'] ?? null,
                ],
            ]);

            // Create wallet for tenant
            $tenant->wallets()->create([
                'balance' => 0,
                'currency' => 'RUB',
                'is_active' => true,
            ]);

            // If user is provided, attach as owner
            if (! empty($data['user_id'])) {
                $user = User::findOrFail($data['user_id']);
                $user->tenants()->attach($tenant->id, [
                    'role' => Role::Owner,
                    'is_active' => true,
                    'accepted_at' => CarbonImmutable::now(),
                ]);
                $user->update(['role' => Role::Owner]);
            }

            // Auto-moderation check
            $this->runAutoModeration($tenant);

            $this->log->channel('audit')->$this->logger->info('Tenant registered', [
                'tenant_id' => $tenant->id,
                'name' => $tenant->name,
                'inn' => $tenant->inn,
                'ip' => request()->ip(),
                'correlation_id' => $data['correlation_id'] ?? null,
            ]);

            $this->eventDispatcher->dispatch(new TenantCreated($tenant));

            return $tenant;
        });
    }

    /**
     * Verify tenant documents
     */
    public function verifyDocuments(Tenant $tenant, array $documents): bool
    {
        $tenant->update([
            'verification_status' => TenantVerificationStatus::ManualReview,
            'meta' => array_merge($tenant->meta ?? [], [
                'documents' => $documents,
            ]),
        ]);

        // Send to manual moderation queue
        // $this->bus->dispatch(new TenantModerationJob($tenant));

        $this->log->channel('audit')->$this->logger->info('Tenant documents submitted for verification', [
            'tenant_id' => $tenant->id,
            'inn' => $tenant->inn,
        ]);

        return true;
    }

    /**
     * Approve tenant verification
     */
    public function approveTenant(Tenant $tenant, ?string $moderatorNotes = null): bool
    {
        $tenant->approveVerification($moderatorNotes);

        // Create welcome bonus
        $tenant->mainWallet()->deposit(1000, 'Welcome bonus');

        $this->log->channel('audit')->$this->logger->info('Tenant approved', [
            'tenant_id' => $tenant->id,
            'inn' => $tenant->inn,
            'moderator_notes' => $moderatorNotes,
        ]);

        $this->eventDispatcher->dispatch(new TenantVerified($tenant));

        return true;
    }

    /**
     * Reject tenant verification
     */
    public function rejectTenant(Tenant $tenant, string $reason): bool
    {
        $tenant->rejectVerification($reason);

        $this->log->channel('audit')->$this->logger->info('Tenant rejected', [
            'tenant_id' => $tenant->id,
            'inn' => $tenant->inn,
            'reason' => $reason,
        ]);

        return true;
    }

    /**
     * Validate INN via external service
     */
    private function validateInn(string $inn): array
    {
        // Placeholder for DaData / FNS API integration
        // In production, use: https://dadata.ru/api/suggest/party/

        $isValid = $this->validateInnChecksum($inn);

        if (! $isValid) {
            return ['valid' => false];
        }

        return [
            'valid' => true,
            'data' => [
                'name' => 'Sample Company',
                'address' => 'Sample Address',
            ],
        ];
    }

    /**
     * Validate INN checksum (basic validation)
     */
    private function validateInnChecksum(string $inn): bool
    {
        $inn = (string) $inn;
        $len = strlen($inn);

        if ($len !== 10 && $len !== 12) {
            return false;
        }

        if (! ctype_digit($inn)) {
            return false;
        }

        if ($len === 10) {
            $weights = [2, 4, 10, 3, 5, 9, 4, 6, 8];
            $sum = 0;
            for ($i = 0; $i < 9; $i++) {
                $sum += (int) $inn[$i] * $weights[$i];
            }
            $checksum = ($sum % 11) % 10;

            return $checksum === (int) $inn[9];
        }

        if ($len === 12) {
            $weights1 = [7, 2, 4, 10, 3, 5, 9, 4, 6, 8];
            $weights2 = [3, 7, 2, 4, 10, 3, 5, 9, 4, 6, 8];
            $sum1 = 0;
            $sum2 = 0;
            for ($i = 0; $i < 10; $i++) {
                $sum1 += (int) $inn[$i] * $weights1[$i];
                $sum2 += (int) $inn[$i] * $weights2[$i];
            }
            $sum2 += (int) $inn[10] * $weights2[10];
            $checksum1 = ($sum1 % 11) % 10;
            $checksum2 = ($sum2 % 11) % 10;

            return $checksum1 === (int) $inn[10] && $checksum2 === (int) $inn[11];
        }

        return false;
    }

    /**
     * Run auto-moderation
     */
    private function runAutoModeration(Tenant $tenant): void
    {
        // Implement ML-based auto-moderation
        // For now, simple rules-based approach

        $score = 100;

        // Deduct for suspicious patterns
        if (str_contains($tenant->name, 'Test')) {
            $score -= 30;
        }

        if ($tenant->email && str_contains($tenant->email, 'tempmail.com')) {
            $score -= 50;
        }

        if ($score >= 80) {
            $tenant->update([
                'verification_status' => TenantVerificationStatus::AutoApproved,
                'is_active' => true,
                'is_verified' => true,
                'verified_at' => CarbonImmutable::now(),
            ]);
        } elseif ($score >= 50) {
            $tenant->update([
                'verification_status' => TenantVerificationStatus::ManualReview,
            ]);
        } else {
            $tenant->update([
                'verification_status' => TenantVerificationStatus::Rejected,
                'moderator_notes' => 'Auto-rejected: Low trust score',
            ]);
        }
    }
}

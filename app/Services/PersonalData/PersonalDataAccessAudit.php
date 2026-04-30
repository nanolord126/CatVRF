<?php

declare(strict_types=1);

namespace App\Services\PersonalData;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\DatabaseManager;
use Illuminate\Http\Request;
use Psr\Log\LoggerInterface;

/**
 * Personal Data Access Audit Service
 * 
 * Logs all access to personal data to ClickHouse for 152-FZ compliance.
 * Tracks who accessed what data, when, and for what purpose.
 * 
 * PRODUCTION MANDATORY — CatVRF 2026 Enterprise Security
 * CRITICAL: All personal data access MUST be logged for audit trails.
 */
final readonly class PersonalDataAccessAudit
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly DatabaseManager $db,
    ) {}

    /**
     * Log personal data access
     */
    public function logAccess(
        User $targetUser,
        string $dataType,
        string $action,
        ?User $accessor = null,
        ?Request $request = null,
        ?string $purpose = null
    ): void {
        $accessorId = $accessor?->id ?? auth()->id();
        $ipAddress = $request?->ip() ?? request()->ip();
        $userAgent = $request?->userAgent() ?? request()->userAgent();

        $this->logToClickHouse([
            'event_type' => 'personal_data_access',
            'target_user_id' => $targetUser->id,
            'target_user_uuid' => $targetUser->uuid,
            'accessor_user_id' => $accessorId,
            'data_type' => $dataType,
            'action' => $action, // 'read', 'update', 'delete', 'export'
            'purpose' => $purpose ?? 'unknown',
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'timestamp' => CarbonImmutable::now()->toIso8601String(),
            'correlation_id' => request()->header('X-Correlation-ID') ?? uniqid('audit_', true),
            'requires_jit_access' => $this->requiresJitAccess($dataType),
        ]);

        $this->logger->info('Personal data access logged', [
            'target_user_id' => $targetUser->id,
            'accessor_id' => $accessorId,
            'data_type' => $dataType,
            'action' => $action,
        ]);
    }

    /**
     * Log consent granted event
     */
    public function logConsentGranted(User $user, \App\Enums\ConsentType $type, Request $request): void
    {
        $this->logToClickHouse([
            'event_type' => 'consent_granted',
            'user_id' => $user->id,
            'user_uuid' => $user->uuid,
            'consent_type' => $type->value,
            'is_biometric' => $type->isBiometric(),
            'requires_enhanced_form' => $type->requiresEnhancedForm(),
            'signature_method' => $request->input('signature_method', 'click'),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'timestamp' => CarbonImmutable::now()->toIso8601String(),
            'correlation_id' => $request->header('X-Correlation-ID') ?? uniqid('audit_', true),
        ]);
    }

    /**
     * Log consent withdrawn event
     */
    public function logConsentWithdrawn(User $user, \App\Enums\ConsentType $type, string $reason): void
    {
        $this->logToClickHouse([
            'event_type' => 'consent_withdrawn',
            'user_id' => $user->id,
            'user_uuid' => $user->uuid,
            'consent_type' => $type->value,
            'is_biometric' => $type->isBiometric(),
            'reason' => $reason,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'timestamp' => CarbonImmutable::now()->toIso8601String(),
            'correlation_id' => request()->header('X-Correlation-ID') ?? uniqid('audit_', true),
        ]);
    }

    /**
     * Log data destruction event
     */
    public function logDataDestruction(
        User $user,
        string $dataType,
        string $reason,
        ?string $jobId = null
    ): void {
        $this->logToClickHouse([
            'event_type' => 'personal_data_destroyed',
            'user_id' => $user->id,
            'user_uuid' => $user->uuid,
            'data_type' => $dataType,
            'reason' => $reason,
            'job_id' => $jobId,
            'performed_by' => auth()->id() ?? 'system',
            'timestamp' => CarbonImmutable::now()->toIso8601String(),
            'correlation_id' => uniqid('destruction_', true),
        ]);
    }

    /**
     * Log biometric data collection
     */
    public function logBiometricCollection(
        User $user,
        string $biometricType,
        string $context
    ): void {
        $this->logToClickHouse([
            'event_type' => 'biometric_data_collected',
            'user_id' => $user->id,
            'user_uuid' => $user->uuid,
            'biometric_type' => $biometricType, // 'face_id', 'behavioral', 'voice'
            'context' => $context, // 'registration', 'verification', 'continuous_auth'
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'timestamp' => CarbonImmutable::now()->toIso8601String(),
            'correlation_id' => request()->header('X-Correlation-ID') ?? uniqid('audit_', true),
        ]);
    }

    /**
     * Log data export event
     */
    public function logDataExport(
        User $user,
        array $dataTypes,
        string $format,
        string $purpose
    ): void {
        $this->logToClickHouse([
            'event_type' => 'personal_data_exported',
            'user_id' => $user->id,
            'user_uuid' => $user->uuid,
            'data_types' => json_encode($dataTypes),
            'format' => $format, // 'json', 'pdf', 'csv'
            'purpose' => $purpose, // 'user_request', 'legal_request'
            'requested_by' => auth()->id() ?? $user->id,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'timestamp' => CarbonImmutable::now()->toIso8601String(),
            'correlation_id' => request()->header('X-Correlation-ID') ?? uniqid('audit_', true),
        ]);
    }

    /**
     * Log data access violation attempt
     */
    public function logAccessViolation(
        User $targetUser,
        string $dataType,
        string $violationType,
        ?User $attemptedBy = null
    ): void {
        $this->logToClickHouse([
            'event_type' => 'access_violation',
            'target_user_id' => $targetUser->id,
            'target_user_uuid' => $targetUser->uuid,
            'attempted_by' => $attemptedBy?->id ?? auth()->id(),
            'data_type' => $dataType,
            'violation_type' => $violationType, // 'no_consent', 'no_jit_access', 'insufficient_permissions'
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'timestamp' => CarbonImmutable::now()->toIso8601String(),
            'correlation_id' => uniqid('violation_', true),
        ]);

        $this->logger->warning('Personal data access violation', [
            'target_user_id' => $targetUser->id,
            'attempted_by' => $attemptedBy?->id,
            'data_type' => $dataType,
            'violation_type' => $violationType,
        ]);
    }

    /**
     * Write audit record to ClickHouse
     */
    private $this->db->tion logToClickHouse(array $data): void
    {
        try {
            DB::connection('clickhouse')
                ->table('personal_data_audit')
                ->insert($data);
        } catch (\Throwable $e) {
            // Fallback to file log if ClickHouse is unavailable
            $this->logger->error('Failed to write to ClickHouse audit log', [
                'error' => $e->getMessage(),
                'data' => $data,
            ]);
$this->db->
            // Try to write to PostgreSQL as fallback
            try {
                DB::table('personal_data_audit_fallback')->insert(array_merge($data, [
                    'created_at' => CarbonImmutable::now(),
                ]));
            } catch (\Throwable $fallbackError) {
                $this->logger->error('Failed to write fallback audit log', [
                    'error' => $fallbackError->getMessage(),
                ]);
            }
        }
    }

    /**
     * Check if data type requires JIT access
     */
    private function requiresJitAccess(string $dataType): bool
    {
        $sensitiveTypes = [
            'full_name',
            'email',
            'phone',
            'inn',
            'passport',
            'biometric_vector',
            'behavioral_profile',
        ];

        return in_array($dataType, $sensitiveTypes, true);
    }
}

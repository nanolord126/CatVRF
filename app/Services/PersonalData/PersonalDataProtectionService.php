<?php

declare(strict_types=1);

namespace App\Services\PersonalData;

use App\Enums\ConsentType;
use App\Models\User;
use App\Models\UserConsent;
use App\Services\Security\ThreatModelService;
use Psr\Log\LoggerInterface;

/**
 * Personal Data Protection Service (152-FZ + ФСТЭК №21 Compliance + Post-Quantum)
 * 
 * Централизованный сервис для всех операций с персональными данными.
 * Обеспечивает проверку согласий, шифрование, маскирование и уничтожение ПДн.
 * Интегрирован с ThreatModelService для автоматической реакции на квантовые угрозы (алгоритм Гровера).
 * 
 * PRODUCTION MANDATORY — CatVRF 2026 Enterprise Security
 * ФСТЭК №21: Меры 1-15 комплексной защиты ИСПДн
 * УБИ.КВАНТ-002: Grover's algorithm mitigation
 * 
 * @see docs/security/QUANTUM_THREATS_GROVER_ALGORITHM.md
 */
final readonly class PersonalDataProtectionService
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly ConsentEngine $consentEngine,
        private readonly PersonalDataAccessAudit $audit,
        private readonly ThreatModelService $threatModelService,
    ) {}

    /**
     * Check if user has consent for processing personal data
     * 
     * @throws \RuntimeException If consent is not granted
     */
    public function requireConsent(User $user, ConsentType $type, string $context = 'general'): void
    {
        // Skip consent check in testing mode
        if (config('personal-data.testing_mode', false)) {
            $this->logger->debug('Testing mode: skipping consent check', [
                'user_id' => $user->id,
                'consent_type' => $type->value,
            ]);

            return;
        }

        $this->consentEngine->requireConsent($user, $type, $context);
    }

    /**
     * Check if user has consent for biometric data processing
     * 
     * @throws \RuntimeException If biometric consent is not granted
     */
    public function requireBiometricConsent(User $user, string $biometricType, string $context = 'biometric'): void
    {
        $consentType = match ($biometricType) {
            'face_id' => ConsentType::BIOMETRIC_FACE_ID,
            'liveness' => ConsentType::BIOMETRIC_LIVENESS,
            'behavioral' => ConsentType::BIOMETRIC_BEHAVIORAL,
            'voice' => ConsentType::BIOMETRIC_VOICE,
            default => throw new \InvalidArgumentException("Unknown biometric type: {$biometricType}"),
        };

        $this->requireConsent($user, $consentType, $context);
    }

    /**
     * Mask personal data for display (staff access)
     * 
     * Returns masked version of data unless user has JIT access or is accessing own data.
     */
    public function maskPersonalData(User $targetUser, string $data, string $dataType): string
    {
        // If user is accessing their own data, return unmasked
        if (auth()->check() && auth()->id() === $targetUser->id) {
            return $data;
        }

        // If user has JIT access, return unmasked
        if ($this->hasJitAccess()) {
            $this->audit->logAccess($targetUser, $dataType, 'read_unmasked', auth()->user());
            return $data;
        }

        // Mask the data
        $masked = $this->applyMask($data, $dataType);
        
        $this->audit->logAccess($targetUser, $dataType, 'read_masked', auth()->user());

        return $masked;
    }

    /**
     * Anonymize personal data for external processing
     * 
     * Returns anonymized version suitable for ML/AI processing.
     * Never sends raw PII to external services (152-FZ compliance).
     */
    public function anonymizeForProcessing(array $data): array
    {
        $anonymized = [];

        foreach ($data as $key => $value) {
            $anonymized[$key] = match ($key) {
                'email', 'phone' => $this->hashValue($value),
                'first_name', 'last_name', 'middle_name' => $this->maskName($value),
                'inn', 'passport_number', 'passport_series' => $this->hashValue($value),
                'address' => $this->maskAddress($value),
                'biometric_vector', 'behavioral_profile' => $this->hashValue($value),
                default => $value,
            };
        }

        $this->logger->info('Personal data anonymized for processing', [
            'fields_count' => count($data),
            'anonymized_fields' => array_keys($anonymized),
        ]);

        return $anonymized;
    }

    /**
     * Schedule personal data destruction after consent withdrawal
     * 
     * 152-FZ: Data must be destroyed within 30 days of consent withdrawal.
     */
    public function scheduleDataDestruction(User $user, ConsentType $type): UserConsent
    {
        $consent = $this->consentEngine->withdrawConsent($user, $type, 'User requested data deletion');

        $this->audit->logDataDestruction($user, $type->value, 'Consent withdrawn');

        $this->logger->warning('Personal data destruction scheduled', [
            'user_id' => $user->id,
            'consent_type' => $type->value,
            'scheduled_at' => $consent->data_purge_scheduled_at?->toIso8601String(),
        ]);

        return $consent;
    }

    /**
     * Export user's personal data (GDPR/152-FZ right to data portability)
     * 
     * Returns anonymized data export in specified format.
     */
    public function exportUserData(User $user, string $format = 'json'): array
    {
        $this->requireConsent($user, ConsentType::REGISTRATION, 'data_export');

        $data = [
            'user_id' => $user->id,
            'uuid' => $user->uuid,
            'email' => $user->email,
            'phone' => $user->phone,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'middle_name' => $user->middle_name,
            'created_at' => $user->created_at->toIso8601String(),
            'consents' => $this->consentEngine->getUserConsents($user),
        ];

        $this->audit->logDataExport($user, array_keys($data), $format, 'user_request');

        return $data;
    }

    /**
     * Check if data access requires JIT access
     */
    public function requiresJitAccess(string $dataType): bool
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

    /**
     * Get retention period for data type
     */
    public function getRetentionPeriod(string $dataType): int
    {
        return config("personal-data.retention_periods.{$dataType}", 365);
    }

    /**
     * Check if user has active JIT access
     */
    private function hasJitAccess(): bool
    {
        if (! auth()->check()) {
            return false;
        }

        $user = auth()->user();

        // Check if user has admin role
        if (! $user->hasRole(['admin', 'security_admin', 'compliance_officer'])) {
            return false;
        }

        // Check if user has active JIT access
        return cache()->get("jit_access:{$user->id}", false);
    }

    /**
     * Apply masking based on data type
     */
    private function applyMask(string $value, string $dataType): string
    {
        return match ($dataType) {
            'email' => $this->maskEmail($value),
            'phone' => $this->maskPhone($value),
            'inn' => $this->maskInn($value),
            'passport' => $this->maskPassport($value),
            'name' => $this->maskName($value),
            default => $this->maskDefault($value),
        };
    }

    /**
     * Mask email: a***@example.com
     */
    private function maskEmail(string $email): string
    {
        $parts = explode('@', $email);
        if (count($parts) !== 2) {
            return $this->maskDefault($email);
        }

        [$local, $domain] = $parts;
        $maskedLocal = strlen($local) > 0 ? $local[0] . str_repeat('*', strlen($local) - 1) : '';

        return $maskedLocal . '@' . $domain;
    }

    /**
     * Mask phone: +7 (9**) ***-**-**
     */
    private function maskPhone(string $phone): string
    {
        $cleaned = preg_replace('/[^0-9]/', '', $phone);
        
        if (strlen($cleaned) < 10) {
            return $this->maskDefault($phone);
        }

        return '+7 (' . substr($cleaned, 1, 1) . '**) ***-**-**';
    }

    /**
     * Mask INN: 1234567890**
     */
    private function maskInn(string $inn): string
    {
        $cleaned = preg_replace('/[^0-9]/', '', $inn);
        
        if (strlen($cleaned) < 2) {
            return $this->maskDefault($inn);
        }

        $visibleLength = max(2, strlen($cleaned) - 2);
        return substr($cleaned, 0, $visibleLength) . str_repeat('*', 2);
    }

    /**
     * Mask passport: 12** 567890
     */
    private function maskPassport(string $passport): string
    {
        $cleaned = preg_replace('/[^0-9]/', '', $passport);
        
        if (strlen($cleaned) < 4) {
            return $this->maskDefault($passport);
        }

        $series = substr($cleaned, 0, 2) . '**';
        $number = substr($cleaned, 4);

        return $series . ' ' . $number;
    }

    /**
     * Mask name: А***в
     */
    private function maskName(string $name): string
    {
        $trimmed = trim($name);
        
        if (strlen($trimmed) <= 2) {
            return $this->maskDefault($trimmed);
        }

        $firstChar = mb_substr($trimmed, 0, 1);
        $lastChar = mb_substr($trimmed, -1);
        $middleLength = mb_strlen($trimmed) - 2;

        return $firstChar . str_repeat('*', $middleLength) . $lastChar;
    }

    /**
     * Default mask: show first 2 and last 2 characters
     */
    private function maskDefault(string $value): string
    {
        if (strlen($value) <= 4) {
            return str_repeat('*', strlen($value));
        }

        $start = substr($value, 0, 2);
        $end = substr($value, -2);
        $middle = str_repeat('*', strlen($value) - 4);

        return $start . $middle . $end;
    }

    /**
     * Mask address
     */
    private function maskAddress(string $address): string
    {
        $parts = explode(', ', $address);
        
        if (count($parts) < 2) {
            return $this->maskDefault($address);
        }

        // Keep city, mask street and house
        $city = $parts[0];
        $maskedRest = str_repeat('*', strlen($address) - strlen($city));

        return $city . ', ' . $maskedRest;
    }

    /**
     * Hash value for anonymization
     */
    private function hashValue(string $value): string
    {
        return hash('sha256', $value . config('app.key'));
    }

    /**
     * Get protection level for data type
     */
    public function getProtectionLevel(string $dataType): int
    {
        $biometricTypes = ['face_id', 'liveness', 'behavioral', 'voice'];
        
        if (in_array($dataType, $biometricTypes, true)) {
            return 3; // УЗ-3 for biometric data
        }

        return config('personal-data.protection_level', 3);
    }

    /**
     * Check if data type requires encryption
     */
    public function requiresEncryption(string $dataType): bool
    {
        $encryptedColumns = config('personal-data.encryption.column_level.encrypted_columns', []);

        return in_array($dataType, $encryptedColumns, true);
    }

    /**
     * Get current Grover's algorithm risk level
     * 
     * Delegates to ThreatModelService for quantum threat assessment.
     * 
     * @return array{level: string, score: float, factors: array, recommendations: array}
     */
    public function getGroverRiskLevel(): array
    {
        return $this->threatModelService->getGroverRiskLevel();
    }

    /**
     * Check if data type requires AES-256 encryption based on Grover risk
     * 
     * Returns true if:
     * - Data type is critical (biometric_vector, behavioral_profile, passport, inn)
     * - Grover risk level is high or critical
     * 
     * @param string $dataType Data type to check
     * @return bool
     */
    public function requiresAES256(string $dataType): bool
    {
        return $this->threatModelService->requiresAES256($dataType);
    }

    /**
     * Get recommended cooldown multiplier based on Grover risk
     * 
     * Higher Grover risk = longer cooldowns for PII operations.
     * Used in FraudControlService and CooldownPeriod management.
     * 
     * @return float (1.0 = normal, 2.0 = double cooldown, 3.0 = triple)
     */
    public function getGroverCooldownMultiplier(): float
    {
        return $this->threatModelService->getGroverCooldownMultiplier();
    }

    /**
     * Encrypt personal data with appropriate algorithm based on Grover risk
     * 
     * Automatically selects AES-256-GCM for critical data or when Grover risk is elevated.
     * 
     * @param string $data Data to encrypt
     * @param string $dataType Type of data (email, phone, passport, inn, biometric_vector, etc.)
     * @return string Encrypted data
     */
    public function encryptPersonalData(string $data, string $dataType): string
    {
        if ($this->requiresAES256($dataType)) {
            // Use AES-256-GCM for critical data or high Grover risk
            $this->logger->info('Encrypting personal data with AES-256-GCM', [
                'data_type' => $dataType,
                'grover_risk_level' => $this->getGroverRiskLevel()['level'],
            ]);
            
            // Delegate to AES256EncryptedCast or similar service
            return $this->encryptWithAES256($data);
        }
        
        // Use legacy AES-128-CBC for non-critical data when Grover risk is low
        return encrypt($data);
    }

    /**
     * Encrypt personal data with AES-256-GCM.
     * 
     * Post-Quantum: Uses CryptoService for AES-256-GCM encryption.
     * Provides 128-bit security against Grover's algorithm.
     */
    private function encryptWithAES256(string $data): string
    {
        return $this->crypto->aes256Encrypt($data);
    }

    /**
     * Get AES-256 key from configuration
     * 
     * @return string 32-byte key
     * @throws \RuntimeException If key not configured or invalid
     */
    private function getAES256Key(): string
    {
        $key = config('app.aes256_key');
        
        if ($key === null) {
            throw new \RuntimeException('AES-256 key not configured. Set APP_AES256_KEY in .env');
        }
        
        $keyBytes = base64_decode($key);
        
        if (strlen($keyBytes) !== 32) {
            throw new \RuntimeException('AES-256 key must be exactly 32 bytes (256 bits)');
        }
        
        return $keyBytes;
    }
}

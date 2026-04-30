<?php

declare(strict_types=1);

namespace App\Services\Security;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Log\LogManager;
use Psr\Log\LoggerInterface;

/**
 * Crypto Service (Post-Quantum Resistant)
 *
 * Единая точка для всех криптографических операций в CatVRF.
 * Обеспечивает:
 * - SHA-256 + pepper для хэширования контактов и векторов
 * - Argon2id + pepper для хэширования паролей
 * - AES-256-GCM для шифрования ПДн
 * - HMAC-SHA256 для integrity checking
 *
 * Post-Quantum Mitigation:
 * - Grover's algorithm: SHA-256 + pepper даёт ~128-бит post-quantum защиту
 * - Shor's algorithm: AES-256-GCM устойчив (симметричное шифрование)
 *
 * PRODUCTION MANDATORY — CatVRF 2026 Enterprise Security
 * ФСТЭК №21: Мера 11 - Шифрование
 * УБИ.КВАНТ-002: Grover's algorithm mitigation
 *
 * @see docs/security/QUANTUM_THREATS_GROVER_ALGORITHM.md
 */
final readonly class CryptoService
{
    private string $pepper;

    private array $legacyPeppers;

    private array $argon2idParams;

    public function __construct(
        private readonly ConfigRepository $config,
        private readonly Hasher $hasher,
        private readonly LoggerInterface $logger,
        private readonly LogManager $log,
    ) {
        $this->pepper = $this->getPepper();
        $this->legacyPeppers = $this->config->get('crypto.legacy_peppers', []);
        $this->argon2idParams = $this->config->get('crypto.argon2id', [
            'memory_cost' => 19456,
            'time_cost' => 2,
            'threads' => 1,
        ]);

        $this->validateConfiguration();
    }

    /**
     * Hash contact information (email/phone) for UniqueContactService.
     *
     * Uses SHA-256 + pepper for post-quantum resistance.
     * Prevents rainbow table attacks and HNDL attacks.
     *
     * Post-Quantum: SHA-256 + pepper ≈ 128-bit security against Grover
     *
     * @param  string  $contact  Email or phone number
     * @return string 64-character hex string
     */
    public function hashContact(string $contact): string
    {
        $normalized = mb_strtolower(trim($contact));
        $algorithm = $this->config->get('crypto.contact_hash_algorithm', 'sha256');

        $hash = hash($algorithm, $normalized . $this->pepper);

        if ($this->config->get('crypto.audit_logging.log_hashing', true)) {
            $this->log->channel('audit')->info('Contact hashed', [
                'algorithm' => $algorithm,
                'hash_length' => strlen($hash),
                'contact_length' => strlen($contact),
            ]);
        }

        return $hash;
    }

    /**
     * Verify contact hash against plaintext (for legacy validation).
     *
     * Checks against current pepper and legacy peppers for migration support.
     *
     * @param  string  $contact  Plaintext contact
     * @param  string  $hash  Stored hash
     * @return bool
     */
    public function verifyContactHash(string $contact, string $hash): bool
    {
        $normalized = mb_strtolower(trim($contact));
        $algorithm = $this->config->get('crypto.contact_hash_algorithm', 'sha256');

        // Check current pepper
        $currentHash = hash($algorithm, $normalized . $this->pepper);
        if (hash_equals($currentHash, $hash)) {
            return true;
        }

        // Check legacy peppers for migration support
        foreach ($this->legacyPeppers as $legacyPepper) {
            $legacyHash = hash($algorithm, $normalized . $legacyPepper);
            if (hash_equals($legacyHash, $hash)) {
                $this->logger->warning('Contact hash verified with legacy pepper', [
                    'contact_length' => strlen($contact),
                ]);

                return true;
            }
        }

        return false;
    }

    /**
     * Hash password using Argon2id + pepper for post-quantum resistance.
     *
     * Format: argon2id$...$pepper_sha256_hash
     *
     * Post-Quantum: Argon2id is memory-hard, resistant to GPU/ASIC/quantum attacks
     * + pepper adds another layer against rainbow tables.
     *
     * @param  string  $password  Plain text password
     * @param  string|null  $salt  Optional per-user salt (recommended)
     * @return string Argon2id hash
     */
    public function hashPassword(string $password, ?string $salt = null): string
    {
        $algorithm = $this->config->get('crypto.password_algorithm', 'argon2id');

        if ($algorithm !== 'argon2id' && $algorithm !== 'argon2i') {
            throw new \RuntimeException("Unsupported password algorithm: {$algorithm}. Use argon2id or argon2i.");
        }

        // Pre-hash with SHA-256 + pepper
        $preHash = hash('sha256', $password . $this->pepper);

        // Add salt if provided
        $input = $salt !== null ? $preHash . $salt : $preHash;

        // Hash with Argon2id
        $argonHash = password_hash($input, PASSWORD_ARGON2ID, $this->argon2idParams);

        if ($argonHash === false) {
            throw new \RuntimeException('Argon2id hashing failed');
        }

        if ($this->config->get('crypto.audit_logging.log_hashing', true)) {
            $this->log->channel('audit')->info('Password hashed', [
                'algorithm' => 'argon2id',
                'memory_cost' => $this->argon2idParams['memory_cost'],
                'time_cost' => $this->argon2idParams['time_cost'],
                'threads' => $this->argon2idParams['threads'],
            ]);
        }

        return $argonHash;
    }

    /**
     * Verify password against hash.
     *
     * Supports legacy bcrypt hashes for migration.
     *
     * @param  string  $password  Plain text password
     * @param  string  $hash  Stored hash
     * @param  string|null  $salt  Optional per-user salt
     * @return bool
     */
    public function verifyPassword(string $password, string $hash, ?string $salt = null): bool
    {
        // Check if it's an Argon2id hash
        if (str_starts_with($hash, '$argon2id$')) {
            $preHash = hash('sha256', $password . $this->pepper);
            $input = $salt !== null ? $preHash . $salt : $preHash;

            return password_verify($input, $hash);
        }

        // Legacy bcrypt support
        if (str_starts_with($hash, '$2y$') || str_starts_with($hash, '$2b$')) {
            $this->logger->warning('Verifying legacy bcrypt password', [
                'hash_prefix' => substr($hash, 0, 10),
            ]);

            return password_verify($password, $hash);
        }

        return false;
    }

    /**
     * Hash behavioral vector for storage.
     *
     * Uses SHA-256(vector . user_salt . pepper) for post-quantum resistance.
     * Similarity comparison is done on hashed representations.
     *
     * Post-Quantum: SHA-256 + per-user salt + pepper ≈ 128-bit security
     *
     * @param  array  $vector  Behavioral vector (typing, mouse, touch patterns)
     * @param  string  $userSalt  Per-user salt for additional security
     * @return string 64-character hex string
     */
    public function hashBehavioralVector(array $vector, string $userSalt): string
    {
        $serialized = json_encode($vector, JSON_THROW_ON_ERROR);
        $algorithm = $this->config->get('crypto.behavioral_hash_algorithm', 'sha256');

        $hash = hash($algorithm, $serialized . $userSalt . $this->pepper);

        if ($this->config->get('crypto.audit_logging.log_hashing', true)) {
            $this->log->channel('audit')->info('Behavioral vector hashed', [
                'algorithm' => $algorithm,
                'vector_size' => count($vector),
                'hash_length' => strlen($hash),
            ]);
        }

        return $hash;
    }

    /**
     * Hash audit log entry for integrity.
     *
     * Uses HMAC-SHA256 for tamper-evident logging.
     *
     * @param  string  $data  Log entry data
     * @param  string  $key  HMAC key (use APP_KEY by default)
     * @return string 64-character hex string
     */
    public function hashAuditLog(string $data, ?string $key = null): string
    {
        $algorithm = $this->config->get('crypto.hmac_algorithm', 'sha256');
        $hmacKey = $key ?? $this->config->get('app.key');

        $hash = hash_hmac($algorithm, $data, $hmacKey);

        if ($this->config->get('crypto.audit_logging.log_hashing', true)) {
            $this->log->channel('audit')->info('Audit log hashed', [
                'algorithm' => $algorithm,
                'data_length' => strlen($data),
            ]);
        }

        return $hash;
    }

    /**
     * Generate HMAC for integrity checking.
     *
     * Used for:
     * - API token signatures
     * - Webhook signatures
     * - Message integrity
     *
     * Post-Quantum: HMAC-SHA256 ≈ 128-bit security against Grover
     *
     * @param  string  $data  Data to sign
     * @param  string|null  $key  HMAC key (default: APP_KEY)
     * @return string 64-character hex string
     */
    public function hmac(string $data, ?string $key = null): string
    {
        $algorithm = $this->config->get('crypto.hmac_algorithm', 'sha256');
        $hmacKey = $key ?? $this->config->get('app.key');

        return hash_hmac($algorithm, $data, $hmacKey);
    }

    /**
     * Verify HMAC signature.
     *
     * @param  string  $data  Original data
     * @param  string  $signature  HMAC signature to verify
     * @param  string|null  $key  HMAC key (default: APP_KEY)
     * @return bool
     */
    public function verifyHmac(string $data, string $signature, ?string $key = null): bool
    {
        $expected = $this->hmac($data, $key);

        return hash_equals($expected, $signature);
    }

    /**
     * Encrypt data using AES-256-GCM.
     *
     * Post-Quantum: AES-256-GCM provides 128-bit security against Grover
     * (Grover reduces 256-bit to 128-bit, which is still secure).
     *
     * @param  string  $data  Data to encrypt
     * @return string Encrypted data with version prefix (v2:base64(iv+tag+ciphertext))
     */
    public function aes256Encrypt(string $data): string
    {
        $key = $this->getAES256Key();
        $iv = random_bytes(16); // 128-bit IV for GCM
        $tag = '';

        $encrypted = openssl_encrypt(
            $data,
            'aes-256-gcm',
            $key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );

        if ($encrypted === false) {
            throw new \RuntimeException('AES-256-GCM encryption failed');
        }

        // Format: v2:IV (16 bytes) + Tag (16 bytes) + Ciphertext
        $version = $this->config->get('crypto.encryption_version', 'v2');
        $result = $version . ':' . base64_encode($iv . $tag . $encrypted);

        if ($this->config->get('crypto.audit_logging.log_encryption', true)) {
            $this->log->channel('audit')->info('Data encrypted with AES-256-GCM', [
                'version' => $version,
                'data_length' => strlen($data),
            ]);
        }

        return $result;
    }

    /**
     * Decrypt data using AES-256-GCM.
     *
     * Supports version prefix for crypto-agility.
     *
     * @param  string  $encrypted  Encrypted data with version prefix
     * @return string Decrypted data
     */
    public function aes256Decrypt(string $encrypted): string
    {
        // Check version prefix
        if (str_contains($encrypted, ':')) {
            [$version, $payload] = explode(':', $encrypted, 2);

            if ($version === 'v2') {
                return $this->decryptAES256GCM($payload);
            }

            if ($version === 'v1') {
                // Legacy v1: Laravel's default encryption
                return decrypt($payload);
            }

            throw new \RuntimeException("Unsupported encryption version: {$version}");
        }

        // Try legacy decryption without version prefix
        try {
            return decrypt($encrypted);
        } catch (\Throwable $e) {
            // Try AES-256-GCM without version prefix
            return $this->decryptAES256GCM($encrypted);
        }
    }

    /**
     * Decrypt using AES-256-GCM with authenticated decryption.
     */
    private function decryptAES256GCM(string $encrypted): string
    {
        $key = $this->getAES256Key();
        $decoded = base64_decode($encrypted);

        if (strlen($decoded) < 32) { // IV (16) + Tag (16)
            throw new \RuntimeException('Invalid encrypted data length');
        }

        $iv = substr($decoded, 0, 16);
        $tag = substr($decoded, 16, 16);
        $ciphertext = substr($decoded, 32);

        $decrypted = openssl_decrypt(
            $ciphertext,
            'aes-256-gcm',
            $key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );

        if ($decrypted === false) {
            if ($this->config->get('crypto.audit_logging.log_decryption_failures', true)) {
                $this->log->channel('audit')->error('AES-256-GCM decryption failed', [
                    'error' => openssl_error_string(),
                ]);
            }

            throw new \RuntimeException('AES-256-GCM decryption failed - authentication tag mismatch');
        }

        return $decrypted;
    }

    /**
     * Generate random salt for per-user security.
     *
     * @param  int  $length  Salt length in bytes (default: 16)
     * @return string Hex-encoded salt
     */
    public function generateSalt(int $length = 16): string
    {
        return bin2hex(random_bytes($length));
    }

    /**
     * Hash document checksum for KYB verification.
     *
     * Uses SHA-256(file_hash . metadata . pepper).
     *
     * @param  string  $fileHash  SHA-256 hash of file
     * @param  array  $metadata  Document metadata
     * @return string 64-character hex string
     */
    public function hashDocumentChecksum(string $fileHash, array $metadata): string
    {
        $serialized = json_encode($metadata, JSON_THROW_ON_ERROR);

        return hash('sha256', $fileHash . $serialized . $this->pepper);
    }

    /**
     * Hash token for Sanctum/PersonalAccessToken.
     *
     * Uses SHA-256(token . pepper) for secure token storage.
     *
     * @param  string  $token  Plain text token
     * @return string 64-character hex string
     */
    public function hashToken(string $token): string
    {
        return hash('sha256', $token . $this->pepper);
    }

    /**
     * Get current pepper from configuration.
     *
     * @return string
     *
     * @throws \RuntimeException If pepper not configured
     */
    private function getPepper(): string
    {
        $pepper = $this->config->get('crypto.pepper');

        if ($pepper === null || $pepper === '') {
            throw new \RuntimeException(
                'CRYPTO_PEPPER not configured. Set CRYPTO_PEPPER in .env ' .
                '(generate with: base64_encode(random_bytes(32)))'
            );
        }

        return $pepper;
    }

    /**
     * Get AES-256 key from configuration.
     *
     * @return string 32-byte key
     *
     * @throws \RuntimeException If key not configured or invalid
     */
    private function getAES256Key(): string
    {
        $key = $this->config->get('crypto.aes256_key') ?? $this->config->get('app.aes256_key');

        if ($key === null || $key === '') {
            throw new \RuntimeException(
                'AES-256 key not configured. Set APP_AES256_KEY in .env ' .
                '(generate with: base64_encode(random_bytes(32)))'
            );
        }

        $keyBytes = base64_decode($key);

        if (strlen($keyBytes) !== 32) {
            throw new \RuntimeException('AES-256 key must be exactly 32 bytes (256 bits)');
        }

        return $keyBytes;
    }

    /**
     * Validate configuration on service instantiation.
     *
     * @throws \RuntimeException If configuration is invalid
     */
    private function validateConfiguration(): void
    {
        // Validate pepper
        try {
            $this->getPepper();
        } catch (\RuntimeException $e) {
            if (app()->environment('production')) {
                throw $e;
            }

            $this->logger->warning('CRYPTO_PEPPER not configured, using fallback for development', [
                'error' => $e->getMessage(),
            ]);
        }

        // Validate AES-256 key
        try {
            $this->getAES256Key();
        } catch (\RuntimeException $e) {
            if (app()->environment('production')) {
                throw $e;
            }

            $this->logger->warning('AES-256 key not configured, encryption will fail in production', [
                'error' => $e->getMessage(),
            ]);
        }

        // Validate Argon2id parameters
        $memory = $this->argon2idParams['memory_cost'] ?? 0;
        if ($memory < 8192) { // Minimum 8 MB
            $this->logger->warning('Argon2id memory_cost too low for production', [
                'memory_cost' => $memory,
                'recommended' => 19456,
            ]);
        }
    }

    /**
     * Get current quantum risk level.
     *
     * @return string low|medium|high|critical
     */
    public function getQuantumRiskLevel(): string
    {
        return $this->config->get('crypto.quantum_risk_level', 'low');
    }

    /**
     * Check if AES-256 encryption is required based on quantum risk.
     *
     * @param  string  $dataType  Data type to check
     * @return bool
     */
    public function requiresAES256(string $dataType): bool
    {
        $riskLevel = $this->getQuantumRiskLevel();
        $protectedTypes = $this->config->get('crypto.protected_data_types', []);

        // Always use AES-256 for protected data types
        if (in_array($dataType, $protectedTypes, true)) {
            return true;
        }

        // Use AES-256 for high/critical risk levels
        return in_array($riskLevel, ['high', 'critical'], true);
    }

    /**
     * Get adaptive parameters based on quantum risk level.
     *
     * @return array{argon2id_memory: int, argon2id_time: int, contact_hash: string, encryption: string}
     */
    public function getAdaptiveParameters(): array
    {
        $riskLevel = $this->getQuantumRiskLevel();

        return $this->config->get("crypto.adaptive_parameters.{$riskLevel}", $this->config->get('crypto.adaptive_parameters.low'));
    }
}

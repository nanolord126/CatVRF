<?php declare(strict_types=1);

namespace App\Services\ML;

use Illuminate\Support\Facades\Log;
use Psr\Log\LoggerInterface;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

/**
 * FraudML Model Encryption Service
 * CANON 2026 - Production Ready
 *
 * Encrypts ML model files with AES-256-GCM and adds digital signatures.
 * Prevents model tampering and unauthorized access.
 * 
 * Critical for security: models contain fraud detection patterns that could be exploited.
 */
final readonly class FraudMLModelEncryption
{
    private const SIGNATURE_ALGORITHM = 'sha256';
    private const ENCRYPTION_KEY_ENV = 'FRAUDML_ENCRYPTION_KEY';
    private const SIGNATURE_KEY_ENV = 'FRAUDML_SIGNATURE_KEY';

    public function __construct(
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Encrypt model file and generate digital signature
     */
    public function encryptModel(string $modelPath): array
    {
        if (!file_exists($modelPath)) {
            throw new \RuntimeException("Model file not found: {$modelPath}");
        }

        $this->logger->info('Encrypting FraudML model', [
            'model_path' => $modelPath,
        ]);

        // Read model file
        $modelContent = file_get_contents($modelPath);
        if ($modelContent === false) {
            throw new \RuntimeException("Failed to read model file: {$modelPath}");
        }

        // Encrypt content
        $encryptedContent = $this->encrypt($modelContent);

        // Generate digital signature
        $signature = $this->generateSignature($encryptedContent);

        // Write encrypted model
        $encryptedPath = $modelPath . '.enc';
        if (file_put_contents($encryptedPath, $encryptedContent) === false) {
            throw new \RuntimeException("Failed to write encrypted model: {$encryptedPath}");
        }

        // Write signature file
        $signaturePath = $modelPath . '.sig';
        if (file_put_contents($signaturePath, $signature) === false) {
            throw new \RuntimeException("Failed to write signature: {$signaturePath}");
        }

        // Calculate file hash for integrity verification
        $fileHash = hash_file(self::SIGNATURE_ALGORITHM, $encryptedPath);

        $this->logger->info('FraudML model encrypted successfully', [
            'model_path' => $modelPath,
            'encrypted_path' => $encryptedPath,
            'signature_path' => $signaturePath,
            'file_hash' => $fileHash,
        ]);

        // Delete original unencrypted file
        unlink($modelPath);

        return [
            'encrypted_path' => $encryptedPath,
            'signature_path' => $signaturePath,
            'file_hash' => $fileHash,
            'is_encrypted' => true,
        ];
    }

    /**
     * Decrypt model file and verify signature
     */
    public function decryptModel(string $encryptedPath, string $signaturePath): string
    {
        if (!file_exists($encryptedPath)) {
            throw new \RuntimeException("Encrypted model file not found: {$encryptedPath}");
        }

        if (!file_exists($signaturePath)) {
            throw new \RuntimeException("Signature file not found: {$signaturePath}");
        }

        $this->logger->info('Decrypting FraudML model', [
            'encrypted_path' => $encryptedPath,
        ]);

        // Read encrypted content
        $encryptedContent = file_get_contents($encryptedPath);
        if ($encryptedContent === false) {
            throw new \RuntimeException("Failed to read encrypted model: {$encryptedPath}");
        }

        // Read signature
        $signature = file_get_contents($signaturePath);
        if ($signature === false) {
            throw new \RuntimeException("Failed to read signature: {$signaturePath}");
        }

        // Verify signature
        if (!$this->verifySignature($encryptedContent, $signature)) {
            throw new \RuntimeException('Model signature verification failed - possible tampering');
        }

        // Decrypt content
        $decryptedContent = $this->decrypt($encryptedContent);

        $this->logger->info('FraudML model decrypted successfully', [
            'encrypted_path' => $encryptedPath,
        ]);

        return $decryptedContent;
    }

    /**
     * Verify model integrity without full decryption
     */
    public function verifyModelIntegrity(string $encryptedPath, string $signaturePath, string $expectedHash): bool
    {
        if (!file_exists($encryptedPath) || !file_exists($signaturePath)) {
            return false;
        }

        // Verify file hash
        $actualHash = hash_file(self::SIGNATURE_ALGORITHM, $encryptedPath);
        if ($actualHash !== $expectedHash) {
            $this->logger->warning('Model file hash mismatch', [
                'expected' => $expectedHash,
                'actual' => $actualHash,
            ]);
            return false;
        }

        // Verify signature
        $encryptedContent = file_get_contents($encryptedPath);
        $signature = file_get_contents($signaturePath);

        if (!$this->verifySignature($encryptedContent, $signature)) {
            $this->logger->warning('Model signature verification failed');
            return false;
        }

        return true;
    }

    /**
     * Encrypt content using AES-256-GCM
     */
    private function encrypt(string $content): string
    {
        $key = $this->getEncryptionKey();
        $nonce = random_bytes(12); // GCM recommended nonce size
        $tag = '';

        $encrypted = openssl_encrypt(
            $content,
            'aes-256-gcm',
            $key,
            OPENSSL_RAW_DATA,
            $nonce,
            $tag
        );

        if ($encrypted === false) {
            throw new \RuntimeException('Encryption failed: ' . openssl_error_string());
        }

        // Combine nonce + tag + encrypted content
        return $nonce . $tag . $encrypted;
    }

    /**
     * Decrypt content using AES-256-GCM
     */
    private function decrypt(string $encryptedContent): string
    {
        $key = $this->getEncryptionKey();
        
        // Extract nonce (12 bytes), tag (16 bytes), and ciphertext
        $nonce = substr($encryptedContent, 0, 12);
        $tag = substr($encryptedContent, 12, 16);
        $ciphertext = substr($encryptedContent, 28);

        $decrypted = openssl_decrypt(
            $ciphertext,
            'aes-256-gcm',
            $key,
            OPENSSL_RAW_DATA,
            $nonce,
            $tag
        );

        if ($decrypted === false) {
            throw new \RuntimeException('Decryption failed: ' . openssl_error_string());
        }

        return $decrypted;
    }

    /**
     * Generate digital signature using HMAC-SHA256
     */
    private function generateSignature(string $content): string
    {
        $key = $this->getSignatureKey();
        return hash_hmac(self::SIGNATURE_ALGORITHM, $content, $key);
    }

    /**
     * Verify digital signature
     */
    private function verifySignature(string $content, string $signature): bool
    {
        $key = $this->getSignatureKey();
        $expectedSignature = hash_hmac(self::SIGNATURE_ALGORITHM, $content, $key);
        
        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Get encryption key from environment
     */
    private function getEncryptionKey(): string
    {
        $key = env(self::ENCRYPTION_KEY_ENV);
        
        if ($key === null) {
            throw new \RuntimeException(
                'Encryption key not set. Set ' . self::ENCRYPTION_KEY_ENV . ' in environment'
            );
        }

        // Key must be 32 bytes for AES-256
        if (strlen($key) < 32) {
            $key = str_pad($key, 32, "\0");
        } elseif (strlen($key) > 32) {
            $key = substr($key, 0, 32);
        }

        return $key;
    }

    /**
     * Get signature key from environment
     */
    private function getSignatureKey(): string
    {
        $key = env(self::SIGNATURE_KEY_ENV);
        
        if ($key === null) {
            throw new \RuntimeException(
                'Signature key not set. Set ' . self::SIGNATURE_KEY_ENV . ' in environment'
            );
        }

        return $key;
    }

    /**
     * Generate encryption keys for environment setup
     */
    public static function generateKeys(): array
    {
        return [
            'encryption_key' => bin2hex(random_bytes(32)),
            'signature_key' => bin2hex(random_bytes(32)),
        ];
    }
}

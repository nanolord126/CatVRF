<?php

declare(strict_types=1);

namespace Modules\Warehouse\Domain\Services;

use Illuminate\Support\Facades\Crypt;
use Modules\Warehouse\Domain\Exceptions\EncryptionException;

/**
 * Encryption Service for 152-ФЗ compliance
 * 
 * Сервис обеспечивает шифрование чувствительных данных в БД:
 * - Шифрование AES-256-GCM для PII
 * - Управление ключами шифрования
 * - Валидация расшифрованных данных
 */
final readonly class EncryptionService
{
    private const ALGORITHM = 'AES-256-GCM';
    private const KEY_LENGTH = 32; // 256 bits
    private const IV_LENGTH = 12; // 96 bits for GCM
    private const TAG_LENGTH = 16; // 128 bits authentication tag

    public function __construct(
        private readonly string $encryptionKey
    ) {
        $this->validateEncryptionKey();
    }

    /**
     * Шифрование данных
     */
    public function encrypt(string $plaintext): string
    {
        try {
            $iv = random_bytes(self::IV_LENGTH);
            $ciphertext = openssl_encrypt(
                $plaintext,
                self::ALGORITHM,
                $this->encryptionKey,
                OPENSSL_RAW_DATA,
                $iv,
                $tag
            );

            if ($ciphertext === false) {
                throw EncryptionException::encryptionFailed(openssl_error_string());
            }

            // Формат: IV (12) + Tag (16) + Ciphertext
            return base64_encode($iv . $tag . $ciphertext);
        } catch (\Exception $e) {
            throw EncryptionException::encryptionFailed($e->getMessage());
        }
    }

    /**
     * Расшифрование данных
     */
    public function decrypt(string $encrypted): string
    {
        try {
            $decoded = base64_decode($encrypted);
            
            if ($decoded === false || strlen($decoded) < self::IV_LENGTH + self::TAG_LENGTH) {
                throw EncryptionException::decryptionFailed('Invalid encrypted data format');
            }

            $iv = substr($decoded, 0, self::IV_LENGTH);
            $tag = substr($decoded, self::IV_LENGTH, self::TAG_LENGTH);
            $ciphertext = substr($decoded, self::IV_LENGTH + self::TAG_LENGTH);

            $plaintext = openssl_decrypt(
                $ciphertext,
                self::ALGORITHM,
                $this->encryptionKey,
                OPENSSL_RAW_DATA,
                $iv,
                $tag
            );

            if ($plaintext === false) {
                throw EncryptionException::decryptionFailed(openssl_error_string());
            }

            return $plaintext;
        } catch (\Exception $e) {
            throw EncryptionException::decryptionFailed($e->getMessage());
        }
    }

    /**
     * Шифрование массива полей
     */
    public function encryptFields(array $data, array $fieldsToEncrypt): array
    {
        foreach ($fieldsToEncrypt as $field) {
            if (isset($data[$field]) && is_string($data[$field]) && !empty($data[$field])) {
                $data[$field] = $this->encrypt($data[$field]);
            }
        }

        return $data;
    }

    /**
     * Расшифрование массива полей
     */
    public function decryptFields(array $data, array $fieldsToDecrypt): array
    {
        foreach ($fieldsToDecrypt as $field) {
            if (isset($data[$field]) && is_string($data[$field]) && !empty($data[$field])) {
                try {
                    $data[$field] = $this->decrypt($data[$field]);
                } catch (EncryptionException $e) {
                    // Если не удалось расшифровать, оставляем как есть
                    $data[$field] = '[ENCRYPTED]';
                }
            }
        }

        return $data;
    }

    /**
     * Проверка, зашифрованы ли данные
     */
    public function isEncrypted(string $value): bool
    {
        $decoded = base64_decode($value);
        
        return $decoded !== false && strlen($decoded) >= self::IV_LENGTH + self::TAG_LENGTH;
    }

    /**
     * Хеширование пароля (для хранения хешей, не для шифрования)
     */
    public function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_ARGON2ID, [
            'memory_cost' => 65536,
            'time_cost' => 4,
            'threads' => 3,
        ]);
    }

    /**
     * Проверка пароля
     */
    public function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    /**
     * Генерация случайного ключа шифрования
     */
    public static function generateEncryptionKey(): string
    {
        return random_bytes(self::KEY_LENGTH);
    }

    /**
     * Валидация ключа шифрования
     */
    private function validateEncryptionKey(): void
    {
        if (strlen($this->encryptionKey) !== self::KEY_LENGTH) {
            throw EncryptionException::invalidKey('Encryption key must be 32 bytes');
        }
    }

    /**
     * Получение полей, требующих шифрования для разных сущностей
     */
    public static function getEncryptableFields(string $entityType): array
    {
        return match ($entityType) {
            'supplier' => ['name', 'contact_person', 'email', 'phone', 'inn', 'kpp', 'ogrn', 'address'],
            'inventory_count' => ['performed_by', 'approved_by', 'notes'],
            'stock_movement' => ['reason', 'notes'],
            'batch' => ['manufacturer', 'supplier_name'],
            default => [],
        };
    }
}

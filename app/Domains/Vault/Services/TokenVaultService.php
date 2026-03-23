<?php declare(strict_types=1);

namespace App\Domains\Vault\Services;

use App\Domains\Vault\Models\EncryptedSecret;
use App\Domains\Vault\Models\VaultAccessLog;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

final class TokenVaultService
{
    private string $correlationId;

    public function __construct(string $correlationId = '')
    {
        $this->correlationId = $correlationId ?: (string) Str::uuid();
    }

    /**
     * Сохранить или обновить секрет с шифрованием AES-256-GCM.
     */
    public function setSecret(string $keyName, mixed $value, int $userId = null): EncryptedSecret
    {
        Log::channel('audit')->info('Vault: Storing secret', [
            'key' => $keyName,
            'user_id' => $userId,
            'correlation_id' => $this->correlationId
        ]);

        $encrypted = Crypt::encrypt($value);

        $secret = EncryptedSecret::updateOrCreate(
            ['key_name' => $keyName],
            [
                'encrypted_payload' => $encrypted,
                'correlation_id' => $this->correlationId,
                'encryption_version' => '1.0'
            ]
        );

        $this->logAccess($secret->id, 'update', $userId);

        return $secret;
    }

    /**
     * Получить секрет с автоматической расшифровкой и аудитом доступа.
     */
    public function getSecret(string $keyName, int $userId = null): mixed
    {
        $secret = EncryptedSecret::where('key_name', $keyName)->first();

        if (!$secret) {
            throw new RuntimeException("Secret [{$keyName}] not found in vault.");
        }

        if ($secret->expires_at && $secret->expires_at->isPast()) {
            throw new RuntimeException("Secret [{$keyName}] has expired.");
        }

        $this->logAccess($secret->id, 'read', $userId);

        try {
            return Crypt::decrypt($secret->encrypted_payload);
        } catch (\Exception $e) {
            Log::error('Vault: Decryption failed', [
                'secret_id' => $secret->id,
                'correlation_id' => $this->correlationId
            ]);
            throw new RuntimeException("Failed to decrypt secret [{$keyName}].");
        }
    }

    /**
     * Логирование доступа к секретам для комплаенса.
     */
    private function logAccess(int $secretId, string $action, ?int $userId): void
    {
        VaultAccessLog::create([
            'secret_id' => $secretId,
            'user_id' => $userId,
            'action' => $action,
            'ip_address' => request()->ip(),
            'correlation_id' => $this->correlationId
        ]);
    }
}
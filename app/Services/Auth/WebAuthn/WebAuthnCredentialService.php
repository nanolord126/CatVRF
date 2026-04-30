<?php

declare(strict_types=1);

namespace App\Services\Auth\WebAuthn;

use Psr\Log\LoggerInterface;

use App\Exceptions\WebAuthnException;

use App\Models\User;
use App\Models\WebauthnCredential;
use App\Services\FraudControlService;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Log\LogManager;

/**
 * WebAuthn Credential Management Service
 *
 * Manages passkey credentials (list, delete, rename).
 * Integrates with fraud control and audit logging.
 *
 * PRODUCTION MANDATORY — CatVRF 2026 Enterprise Security
 */
final readonly class WebAuthnCredentialService
{
    public function __construct(private readonly LoggerInterface $logger,
        private readonly FraudControlService $fraudControl,
        private readonly AuditService $audit,
        private readonly Request $request,
        private readonly LogManager $log,) {}

    /**
     * Get all credentials for a user
     *
     * @param  User  $user  User to get credentials for
     * @return Collection
     */
    public function getUserCredentials(User $user)
    {
        return WebauthnCredential::where('user_id', $user->id)
            ->when(function_exists('tenant') && tenant(), fn ($q) => $q->where('tenant_id', tenant()->id))
            ->orderBy('last_used_at', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get a specific credential
     *
     * @param  User  $user  User requesting the credential
     * @param  int  $credentialId  Credential ID
     *
     * @throws \Exception
     */
    public function getCredential(User $user, int $credentialId): WebauthnCredential
    {
        $credential = WebauthnCredential::where('id', $credentialId)
            ->where('user_id', $user->id)
            ->first();

        if (! $credential) {
            throw new WebAuthnException('Credential not found');
        }

        return $credential;
    }

    /**
     * Rename a credential
     *
     * @param  User  $user  User renaming the credential
     * @param  int  $credentialId  Credential ID
     * @param  string  $name  New name
     *
     * @throws \Exception
     */
    public function renameCredential(User $user, int $credentialId, string $name): WebauthnCredential
    {
        // Fraud check
        $this->fraudControl->check(
            userId: $user->id,
            operationType: 'passkey_rename',
            amount: 0,
            ipAddress: $this->request->ip(),
            deviceFingerprint: $this->request->header('User-Agent'),
        );

        $credential = $this->getCredential($user, $credentialId);

        $credential->name = $name;
        $credential->save();

        // Audit log
        $this->audit->record(
            action: 'passkey_rename',
            subjectType: WebauthnCredential::class,
            subjectId: $credential->id,
            oldValues: ['name' => $credential->getOriginal('name')],
            newValues: ['name' => $name],
        );

        $this->log->channel('security')->$this->logger->info('Passkey renamed', [
            'user_id' => $user->id,
            'credential_id' => $credential->id,
            'new_name' => $name,
        ]);

        return $credential;
    }

    /**
     * Delete a credential
     *
     * @param  User  $user  User deleting the credential
     * @param  int  $credentialId  Credential ID
     * @param  bool  $revokeSessions  Whether to revoke all sessions for this credential
     *
     * @throws \Exception
     */
    public function deleteCredential(User $user, int $credentialId, bool $revokeSessions = true): bool
    {
        // Fraud check
        $this->fraudControl->check(
            userId: $user->id,
            operationType: 'passkey_delete',
            amount: 0,
            ipAddress: $this->request->ip(),
            deviceFingerprint: $this->request->header('User-Agent'),
        );

        $credential = $this->getCredential($user, $credentialId);

        // Check if this is the last credential
        $credentialCount = WebauthnCredential::where('user_id', $user->id)->count();

        if ($credentialCount === 1) {
            throw new WebAuthnException('Cannot delete the last passkey. Add another passkey first.');
        }

        $credentialIdStr = $credential->credential_id;
        $credentialName = $credential->name;

        // Revoke sessions if requested
        if ($revokeSessions) {
            $this->revokeCredentialSessions($user, $credential);
        }

        // Delete credential
        $credential->delete();

        // Audit log
        $this->audit->record(
            action: 'passkey_delete',
            subjectType: WebauthnCredential::class,
            subjectId: $credential->id,
            oldValues: [
                'credential_id' => $credentialIdStr,
                'credential_name' => $credentialName,
            ],
            newValues: [
                'revoked_sessions' => $revokeSessions,
            ],
        );

        $this->log->channel('security')->warning('Passkey deleted', [
            'user_id' => $user->id,
            'credential_id' => $credentialIdStr,
            'credential_name' => $credentialName,
        ]);

        return true;
    }

    /**
     * Get credential statistics for a user
     *
     * @param  User  $user  User
     */
    public function getCredentialStats(User $user): array
    {
        $credentials = $this->getUserCredentials($user);

        return [
            'total' => $credentials->count(),
            'platform' => $credentials->filter(fn ($c) => $c->isPlatformAuthenticator())->count(),
            'syncable' => $credentials->filter(fn ($c) => $c->isSynced())->count(),
            'single_device' => $credentials->filter(fn ($c) => ! $c->isSynced())->count(),
            'last_used' => $credentials->first()?->last_used_at?->toIso8601String(),
        ];
    }

    /**
     * Revoke all sessions associated with a credential
     *
     * @param  User  $user  User
     * @param  WebauthnCredential  $credential  Credential
     */
    private function revokeCredentialSessions(User $user, WebauthnCredential $credential): void
    {
        // In production, revoke Sanctum tokens associated with this credential
        // This requires tracking which token was issued with which credential

        // For now, revoke all tokens except current
        $user->tokens()->where('name', '!=', 'passkey-auth')->delete();

        $this->log->channel('security')->$this->logger->info('Sessions revoked for credential', [
            'user_id' => $user->id,
            'credential_id' => $credential->credential_id,
        ]);
    }
}

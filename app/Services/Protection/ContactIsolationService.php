<?php

declare(strict_types=1);

namespace App\Services\Protection;

use App\Enums\ProfileType;
use App\Exceptions\ContactAlreadyUsedException;
use App\Exceptions\ContactLockedException;
use App\Models\Tenant;
use App\Models\UniqueContact;
use App\Models\User;
use App\Services\Security\CryptoService;
use Illuminate\Cache\CacheManager;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Database\DatabaseManager;
use Illuminate\Log\LogManager;

/**
 * Contact Isolation Service
 *
 * Enforces zero-duplicate policy: one email/phone = one profile (client OR business).
 * All contacts are hashed for 152-ФZ / GDPR compliance using SHA-256 + pepper.
 *
 * Post-Quantum: SHA-256 + pepper provides ~128-bit security against Grover's algorithm.
 *
 * PRODUCTION MANDATORY — CatVRF 2026 Enterprise Security
 * УБИ.КВАНТ-002: Grover's algorithm mitigation
 */
final readonly class ContactIsolationService
{
    private const CACHE_TTL_MINUTES = 60;

    public function __construct(
        private readonly CacheManager $cache,
        private readonly ConfigRepository $config,
        private readonly DatabaseManager $db,
        private readonly CryptoService $crypto,
        private readonly LogManager $log,
    ) {}

    /**
     * Check if email/phone are available for registration.
     *
     * @param  string|null  $email  Email to check
     * @param  string|null  $phone  Phone to check
     * @param  ProfileType  $requestedType  Profile type being registered (client/business)
     * @return array{available: bool, reason?: string, existing_type?: ProfileType}
     *
     * @throws ContactLockedException
     */
    public function checkAvailability(
        ?string $email,
        ?string $phone,
        ProfileType $requestedType,
    ): array {
        $emailHash = $email ? $this->hashContact($email) : null;
        $phoneHash = $phone ? $this->hashContact($phone) : null;

        // Check cache first for performance
        $cacheKey = $this->getCacheKey($emailHash, $phoneHash);
        if ($this->cache->has($cacheKey)) {
            return $this->cache->get($cacheKey);
        }

        $result = $this->checkAvailabilityInDatabase($emailHash, $phoneHash, $requestedType);

        // Cache result
        $this->cache->put($cacheKey, $result, now()->addMinutes(self::CACHE_TTL_MINUTES));

        return $result;
    }

    /**
     * Register contact after successful verification.
     *
     * @param  User|Tenant  $entity  User or Tenant entity
     * @param  string  $email  Email address
     * @param  string|null  $phone  Phone number
     * @param  ProfileType  $profileType  Profile type (client/business)
     * @param  int|null  $tenantId  Tenant ID (for business profiles)
     * @param  int|null  $createdBy  User ID who registered this contact
     * @return UniqueContact
     */
    public function registerContact(
        User|Tenant $entity,
        string $email,
        ?string $phone,
        ProfileType $profileType,
        ?int $tenantId = null,
        ?int $createdBy = null,
    ): UniqueContact {
        $emailHash = $this->hashContact($email);
        $phoneHash = $phone ? $this->hashContact($phone) : null;

        // Double-check availability before registration
        $availability = $this->checkAvailabilityInDatabase($emailHash, $phoneHash, $profileType);
        if (! $availability['available']) {
            throw new ContactAlreadyUsedException(
                $availability['reason'] ?? 'Contact already in use',
                $availability['existing_type'] ?? null,
            );
        }

        return $this->db->transaction(function () use (
            $entity,
            $email,
            $phone,
            $emailHash,
            $phoneHash,
            $profileType,
            $tenantId,
            $createdBy,
        ) {
            // Create unique contact record
            $contact = UniqueContact::create([
                'email_hashed' => $emailHash,
                'phone_hashed' => $phoneHash,
                'profile_type' => $profileType,
                'entity_type' => $entity::class,
                'entity_id' => $entity->id,
                'tenant_id' => $tenantId,
                'registered_at' => now(),
                'last_verified_at' => now(),
                'created_by' => $createdBy,
            ]);

            // Update entity's primary profile flag
            if ($entity instanceof User) {
                $entity->update(['is_primary_profile' => true]);
            } elseif ($entity instanceof Tenant) {
                $entity->update(['is_primary_profile' => true]);
            }

            // Invalidate cache
            $this->invalidateCache($emailHash, $phoneHash);

            // Log registration
            $this->log->channel('audit')->info('Contact registered', [
                'contact_id' => $contact->id,
                'entity_type' => $entity::class,
                'entity_id' => $entity->id,
                'profile_type' => $profileType->value,
                'tenant_id' => $tenantId,
                'created_by' => $createdBy,
            ]);

            return $contact;
        });
    }

    /**
     * Release contact when profile is deleted or contact is changed.
     *
     * @param  User|Tenant  $entity  Entity whose contact should be released
     * @return bool
     */
    public function releaseContact(User|Tenant $entity): bool
    {
        $contact = UniqueContact::where('entity_type', $entity::class)
            ->where('entity_id', $entity->id)
            ->first();

        if (! $contact) {
            return true; // No contact to release
        }

        return $this->db->transaction(function () use ($contact, $entity) {
            // Soft delete contact (152-ФZ compliance)
            $contact->delete();

            // Invalidate cache
            $this->invalidateCache($contact->email_hashed, $contact->phone_hashed);

            // Log release
            $this->log->channel('audit')->info('Contact released', [
                'contact_id' => $contact->id,
                'entity_type' => $entity::class,
                'entity_id' => $entity->id,
            ]);

            return true;
        });
    }

    /**
     * Lock contact for profile transition or recovery.
     *
     * @param  User|Tenant  $entity  Entity whose contact should be locked
     * @param  int  $hours  Lock duration in hours
     * @return bool
     */
    public function lockContact(User|Tenant $entity, int $hours = 72): bool
    {
        $contact = UniqueContact::where('entity_type', $entity::class)
            ->where('entity_id', $entity->id)
            ->first();

        if (! $contact) {
            return false;
        }

        $contact->lock($hours);

        // Update entity's lock timestamp
        if ($entity instanceof User) {
            $entity->update(['contact_locked_at' => now()]);
        } elseif ($entity instanceof Tenant) {
            $entity->update(['contact_locked_at' => now()]);
        }

        // Invalidate cache
        $this->invalidateCache($contact->email_hashed, $contact->phone_hashed);

        $this->log->channel('audit')->info('Contact locked', [
            'contact_id' => $contact->id,
            'entity_type' => $entity::class,
            'entity_id' => $entity->id,
            'hours' => $hours,
        ]);

        return true;
    }

    /**
     * Unlock contact.
     *
     * @param  User|Tenant  $entity  Entity whose contact should be unlocked
     * @return bool
     */
    public function unlockContact(User|Tenant $entity): bool
    {
        $contact = UniqueContact::where('entity_type', $entity::class)
            ->where('entity_id', $entity->id)
            ->first();

        if (! $contact) {
            return false;
        }

        $contact->unlock();

        // Update entity's lock timestamp
        if ($entity instanceof User) {
            $entity->update(['contact_locked_at' => null]);
        } elseif ($entity instanceof Tenant) {
            $entity->update(['contact_locked_at' => null]);
        }

        // Invalidate cache
        $this->invalidateCache($contact->email_hashed, $contact->phone_hashed);

        $this->log->channel('audit')->info('Contact unlocked', [
            'contact_id' => $contact->id,
            'entity_type' => $entity::class,
            'entity_id' => $entity->id,
        ]);

        return true;
    }

    /**
     * Check if contact is locked.
     *
     * @param  User|Tenant  $entity  Entity to check
     * @return bool
     */
    public function isContactLocked(User|Tenant $entity): bool
    {
        $contact = UniqueContact::where('entity_type', $entity::class)
            ->where('entity_id', $entity->id)
            ->first();

        if (! $contact) {
            return false;
        }

        return $contact->isLocked();
    }

    /**
     * Hash contact information for privacy (SHA-256 + pepper).
     *
     * Uses CryptoService for SHA-256 + pepper hashing.
     * Post-Quantum: SHA-256 + pepper ≈ 128-bit security against Grover.
     *
     * @param  string  $contact  Email or phone
     * @return string 64-character hex string
     */
    private function hashContact(string $contact): string
    {
        return $this->crypto->hashContact($contact);
    }

    /**
     * Check availability in database.
     *
     * @param  string|null  $emailHash  Hashed email
     * @param  string|null  $phoneHash  Hashed phone
     * @param  ProfileType  $requestedType  Requested profile type
     * @return array{available: bool, reason?: string, existing_type?: ProfileType}
     */
    private function checkAvailabilityInDatabase(
        ?string $emailHash,
        ?string $phoneHash,
        ProfileType $requestedType,
    ): array {
        // Check email
        if ($emailHash) {
            $emailContact = UniqueContact::withTrashed()
                ->byEmailHash($emailHash)
                ->first();

            if ($emailContact && $emailContact->trashed()) {
                // Soft-deleted contact - check if enough time has passed (152-ФZ retention)
                $retentionDays = $this->config->get('protection.contact_retention_days', 90);
                if ($emailContact->deleted_at->diffInDays(now()) < $retentionDays) {
                    return [
                        'available' => false,
                        'reason' => 'Этот email был использован ранее. Пожалуйста, войдите в существующий аккаунт или воспользуйтесь восстановлением доступа.',
                        'existing_type' => $emailContact->profile_type,
                    ];
                }
            } elseif ($emailContact) {
                // Active contact - check if locked
                if ($emailContact->isLocked()) {
                    throw new ContactLockedException(
                        'Контакт временно заблокирован. Пожалуйста, попробуйте позже или обратитесь в поддержку.',
                    );
                }

                // Check profile type mismatch
                if ($emailContact->profile_type !== $requestedType) {
                    return [
                        'available' => false,
                        'reason' => sprintf(
                            'Этот email уже используется как %s. Для регистрации бизнеса необходимо использовать другой контакт или пройти KYB-процедуру.',
                            $emailContact->profile_type->label(),
                        ),
                        'existing_type' => $emailContact->profile_type,
                    ];
                }

                return [
                    'available' => false,
                    'reason' => 'Этот email уже зарегистрирован. Пожалуйста, войдите в существующий аккаунт.',
                    'existing_type' => $emailContact->profile_type,
                ];
            }
        }

        // Check phone
        if ($phoneHash) {
            $phoneContact = UniqueContact::withTrashed()
                ->byPhoneHash($phoneHash)
                ->first();

            if ($phoneContact && $phoneContact->trashed()) {
                $retentionDays = $this->config->get('protection.contact_retention_days', 90);
                if ($phoneContact->deleted_at->diffInDays(now()) < $retentionDays) {
                    return [
                        'available' => false,
                        'reason' => 'Этот телефон был использован ранее. Пожалуйста, войдите в существующий аккаунт или воспользуйтесь восстановлением доступа.',
                        'existing_type' => $phoneContact->profile_type,
                    ];
                }
            } elseif ($phoneContact) {
                if ($phoneContact->isLocked()) {
                    throw new ContactLockedException(
                        'Контакт временно заблокирован. Пожалуйста, попробуйте позже или обратитесь в поддержку.',
                    );
                }

                if ($phoneContact->profile_type !== $requestedType) {
                    return [
                        'available' => false,
                        'reason' => sprintf(
                            'Этот телефон уже используется как %s. Для регистрации бизнеса необходимо использовать другой контакт или пройти KYB-процедуру.',
                            $phoneContact->profile_type->label(),
                        ),
                        'existing_type' => $phoneContact->profile_type,
                    ];
                }

                return [
                    'available' => false,
                    'reason' => 'Этот телефон уже зарегистрирован. Пожалуйста, войдите в существующий аккаунт.',
                    'existing_type' => $phoneContact->profile_type,
                ];
            }
        }

        return ['available' => true];
    }

    /**
     * Get cache key for contact availability check.
     *
     * @param  string|null  $emailHash  Hashed email
     * @param  string|null  $phoneHash  Hashed phone
     * @return string
     */
    private function getCacheKey(?string $emailHash, ?string $phoneHash): string
    {
        return "contact_availability:{$emailHash}:{$phoneHash}";
    }

    /**
     * Invalidate cache for contact.
     *
     * @param  string|null  $emailHash  Hashed email
     * @param  string|null  $phoneHash  Hashed phone
     * @return void
     */
    private function invalidateCache(?string $emailHash, ?string $phoneHash): void
    {
        $this->cache->forget($this->getCacheKey($emailHash, $phoneHash));
    }
}

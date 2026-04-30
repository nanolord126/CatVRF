<?php

declare(strict_types=1);

namespace App\Services\Protection;

use App\Enums\ProfileType;
use App\Exceptions\ContactAlreadyUsedException;
use App\Exceptions\ProfileTransitionNotAllowedException;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Http\Request;
use Psr\Log\LoggerInterface;

/**
 * Profile Isolation Guard
 *
 * Comprehensive guard that enforces client/business separation before any
 * registration, invitation, or profile transition operation.
 *
 * PRODUCTION MANDATORY — CatVRF 2026 Enterprise Security
 */
final readonly class ProfileIsolationGuard
{
    public function __construct(
        private readonly ContactIsolationService $contactIsolation,
        private readonly AuthFactory $auth,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Guard user registration (B2C client).
     *
     * @param  string  $email  User email
     * @param  string|null  $phone  User phone
     * @return void
     *
     * @throws ContactAlreadyUsedException
     */
    public function guardClientRegistration(string $email, ?string $phone = null): void
    {
        $availability = $this->contactIsolation->checkAvailability(
            $email,
            $phone,
            ProfileType::Client,
        );

        if (! $availability['available']) {
            $this->logger->warning('Client registration blocked - contact already in use', [
                'email' => $email,
                'phone' => $phone,
                'reason' => $availability['reason'],
                'existing_type' => $availability['existing_type']?->value,
            ]);

            throw new ContactAlreadyUsedException(
                $availability['reason'] ?? 'Contact already in use',
                $availability['existing_type'],
            );
        }
    }

    /**
     * Guard business registration (B2B/Tenant).
     *
     * @param  string  $email  Business email
     * @param  string|null  $phone  Business phone
     * @return void
     *
     * @throws ContactAlreadyUsedException
     */
    public function guardBusinessRegistration(string $email, ?string $phone = null): void
    {
        $availability = $this->contactIsolation->checkAvailability(
            $email,
            $phone,
            ProfileType::Business,
        );

        if (! $availability['available']) {
            $this->logger->warning('Business registration blocked - contact already in use', [
                'email' => $email,
                'phone' => $phone,
                'reason' => $availability['reason'],
                'existing_type' => $availability['existing_type']?->value,
            ]);

            throw new ContactAlreadyUsedException(
                $availability['reason'] ?? 'Contact already in use',
                $availability['existing_type'],
            );
        }
    }

    /**
     * Guard tenant invitation (staff member).
     *
     * @param  string  $email  Invitee email
     * @param  Tenant  $tenant  Tenant inviting
     * @return void
     *
     * @throws ContactAlreadyUsedException
     */
    public function guardTenantInvitation(string $email, Tenant $tenant): void
    {
        $availability = $this->contactIsolation->checkAvailability(
            $email,
            null,
            ProfileType::Business,
        );

        // If email is used as client, allow invitation but warn
        if (! $availability['available'] && $availability['existing_type'] === ProfileType::Client) {
            $this->logger->warning('Invitation sent to client email - profile conflict possible', [
                'email' => $email,
                'tenant_id' => $tenant->id,
            ]);

            // Allow invitation but this should trigger KYB flow
            return;
        }

        // If email is used as business in another tenant, block
        if (! $availability['available'] && $availability['existing_type'] === ProfileType::Business) {
            throw new ContactAlreadyUsedException(
                'Этот email уже используется в другом бизнесе. Пожалуйста, используйте другой контакт.',
                $availability['existing_type'],
            );
        }
    }

    /**
     * Guard profile transition (client -> business or business -> client).
     *
     * This is a high-security operation that requires:
     * - 72-hour cooldown
     * - Full KYB verification
     * - Manual moderation
     *
     * @param  User|Tenant  $entity  Entity to transition
     * @param  ProfileType  $targetType  Target profile type
     * @return void
     *
     * @throws ProfileTransitionNotAllowedException
     */
    public function guardProfileTransition(User|Tenant $entity, ProfileType $targetType): void
    {
        // Check if contact is locked
        if ($this->contactIsolation->isContactLocked($entity)) {
            throw new ProfileTransitionNotAllowedException(
                'Контакт заблокирован. Пожалуйста, дождитесь окончания периода охлаждения или обратитесь в поддержку.',
            );
        }

        // Check if entity already has the target type (no transition needed)
        if ($entity instanceof User && $entity->role->isBusiness() && $targetType === ProfileType::Business) {
            return;
        }

        if ($entity instanceof User && $entity->role === \App\Enums\Role::Customer && $targetType === ProfileType::Client) {
            return;
        }

        // Profile transition requires explicit KYB flow
        $this->logger->warning('Profile transition attempted - requires KYB flow', [
            'entity_type' => $entity::class,
            'entity_id' => $entity->id,
            'target_type' => $targetType->value,
        ]);

        throw new ProfileTransitionNotAllowedException(
            'Смена типа профиля требует прохождения KYB-процедуры с модерацией. Пожалуйста, обратитесь в поддержку.',
        );
    }

    /**
     * Guard contact change (email/phone update).
     *
     * Contact changes require:
     * - 72-hour cooldown
     * - Re-verification
     * - Fraud check
     *
     * @param  User|Tenant  $entity  Entity changing contact
     * @param  string  $newEmail  New email
     * @param  string|null  $newPhone  New phone
     * @return void
     *
     * @throws ContactAlreadyUsedException
     */
    public function guardContactChange(User|Tenant $entity, string $newEmail, ?string $newPhone = null): void
    {
        // Determine current profile type
        $profileType = $entity instanceof User && $entity->role->isBusiness()
            ? ProfileType::Business
            : ProfileType::Client;

        $availability = $this->contactIsolation->checkAvailability(
            $newEmail,
            $newPhone,
            $profileType,
        );

        if (! $availability['available']) {
            $this->logger->warning('Contact change blocked - new contact already in use', [
                'entity_type' => $entity::class,
                'entity_id' => $entity->id,
                'new_email' => $newEmail,
                'reason' => $availability['reason'],
            ]);

            throw new ContactAlreadyUsedException(
                $availability['reason'] ?? 'New contact already in use',
                $availability['existing_type'],
            );
        }
    }

    /**
     * Guard social login registration.
     *
     * @param  string  $email  Social login email
     * @param  Request  $request  HTTP request
     * @return void
     *
     * @throws ContactAlreadyUsedException
     */
    public function guardSocialRegistration(string $email, Request $request): void
    {
        $availability = $this->contactIsolation->checkAvailability(
            $email,
            null,
            ProfileType::Client,
        );

        if (! $availability['available']) {
            $this->logger->warning('Social registration blocked - contact already in use', [
                'email' => $email,
                'provider' => $request->input('provider'),
                'reason' => $availability['reason'],
            ]);

            throw new ContactAlreadyUsedException(
                $availability['reason'] ?? 'Contact already in use',
                $availability['existing_type'],
            );
        }
    }

    /**
     * Check if authenticated user can access tenant.
     *
     * @param  Tenant  $tenant  Tenant to access
     * @return bool
     */
    public function canAccessTenant(Tenant $tenant): bool
    {
        $user = $this->auth->guard()->user();

        if (! $user) {
            return false;
        }

        // Platform admins can access any tenant
        if ($user->role?->isPlatformAdmin()) {
            return true;
        }

        // Check if user has active role in tenant
        return $tenant->hasUser($user->id);
    }

    /**
     * Check if authenticated user can perform owner-level operations.
     *
     * @param  Tenant  $tenant  Tenant context
     * @return bool
     */
    public function canPerformOwnerOperations(Tenant $tenant): bool
    {
        $user = $this->auth->guard()->user();

        if (! $user) {
            return false;
        }

        // Platform admins can perform owner operations
        if ($user->role?->isPlatformAdmin()) {
            return true;
        }

        // Check if user is owner in tenant
        return $tenant->userHasRole($user->id, \App\Enums\Role::Owner);
    }
}

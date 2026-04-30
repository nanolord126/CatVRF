<?php

declare(strict_types=1);

namespace App\Services\Auth;

use Illuminate\Contracts\Events\Dispatcher as EventDispatcher;

use Psr\Log\LoggerInterface;

use App\Enums\ProfileType;
use App\Enums\Role;
use App\Enums\UserStatus;
use App\Events\Auth\UserRegistered;
use App\Models\Tenant;
use App\Models\TenantInvitation;
use App\Models\User;
use App\Services\Protection\ContactIsolationService;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Database\DatabaseManager;
use Illuminate\Log\LogManager;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use App\Traits\WithAuditLogging;
use App\Services\Security\AuditService;

final class RegistrationService
{
    use WithAuditLogging;

    public function __construct(
        private readonly EventDispatcher $eventDispatcher,
        private readonly LoggerInterface $logger,
        private readonly FraudControlService $fraudControl,
        private readonly DatabaseManager $db,
        private readonly Hasher $hash,
        private readonly LogManager $log,
        private readonly ContactIsolationService $contactIsolation,
        private readonly AuditService $audit,
    ) {}

    /**
     * Register new user (B2C or via invitation)
     */
    public function registerUser(array $data): User
    {
        return $this->db->transaction(function () use ($data) {
            $this->fraudControl->check(
                $data['user_id'] ?? 0,
                'user_registration',
                $data['tenant_id'] ?? 0,
                request()->ip(),
                null,
                $data['correlation_id'] ?? Str::uuid()->toString(),
            );

            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'password' => $this->hash->make($data['password']),
                'role' => Role::Customer,
                'status' => UserStatus::Pending,
                'tenant_id' => $data['tenant_id'] ?? null,
                'correlation_id' => $data['correlation_id'] ?? null,
            ]);

            // Handle invitation if present
            if (! empty($data['invite_code'])) {
                $this->acceptInvitation($user, $data['invite_code']);
            }

            // Send verification email
            $user->sendEmailVerificationNotification();

            // Log registration
            $this->log->channel('audit')->$this->logger->info('User registered', [
                'user_id' => $user->id,
                'email' => $user->email,
                'ip' => request()->ip(),
                'correlation_id' => $data['correlation_id'] ?? null,
            ]);

            // Dispatch event
            $this->eventDispatcher->dispatch(new UserRegistered($user));

            return $user;
        });
    }

    /**
     * Register via social provider
     */
    public function registerSocial(array $data): User
    {
        return $this->db->transaction(function () use ($data) {
            $this->fraudControl->check(
                0,
                'social_registration',
                0,
                request()->ip(),
                null,
                $data['correlation_id'] ?? Str::uuid()->toString(),
            );

            // Contact isolation check for social registration
            $isNewUser = ! User::where('email', $data['email'])->exists();
            if ($isNewUser) {
                $this->contactIsolation->checkAvailability(
                    $data['email'],
                    $data['phone'] ?? null,
                    ProfileType::Client,
                );
            }

            $user = User::updateOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'password' => $this->hash->make(Str::random(32)), // Random password for social users
                    'role' => Role::Customer,
                    'status' => UserStatus::Active, // Auto-activate social users
                    'email_verified_at' => CarbonImmutable::now(),
                    'phone' => $data['phone'] ?? null,
                    'correlation_id' => $data['correlation_id'] ?? null,
                ]
            );

            // Register contact if new user
            if ($isNewUser) {
                $this->contactIsolation->registerContact(
                    $user,
                    $user->email,
                    $user->phone,
                    ProfileType::Client,
                    null,
                    null,
                );
            }

            // Link social account
            $user->socialAccounts()->create([
                'provider' => $data['provider'],
                'provider_id' => $data['provider_id'],
                'provider_token' => $data['provider_token'] ?? null,
                'provider_data' => $data['provider_data'] ?? null,
            ]);

            $this->log->channel('audit')->$this->logger->info('User registered via social', [
                'user_id' => $user->id,
                'email' => $user->email,
                'provider' => $data['provider'],
                'ip' => request()->ip(),
                'correlation_id' => $data['correlation_id'] ?? null,
            ]);

            return $user;
        });
    }

    /**
     * Verify user email
     */
    public function verifyEmail(User $user, string $token): bool
    {
        if (! hash_equals(sha1($user->getEmailForVerification()), $token)) {
            throw ValidationException::withMessages(['token' => ['Invalid verification token']]);
        }

        if ($user->hasVerifiedEmail()) {
            return true;
        }

        return $user->update([
            'email_verified_at' => CarbonImmutable::now(),
            'status' => UserStatus::Active,
        ]);
    }

    /**
     * Verify user phone
     */
    public function verifyPhone(User $user, string $code): bool
    {
        // Implement SMS verification logic
        $expectedCode = cache()->get("phone_verification:{$user->id}");

        if (! $expectedCode || $expectedCode !== $code) {
            throw ValidationException::withMessages(['code' => ['Invalid verification code']]);
        }

        cache()->forget("phone_verification:{$user->id}");

        return $user->update([
            'phone_verified_at' => CarbonImmutable::now(),
        ]);
    }

    /**
     * Send phone verification code
     */
    public function sendPhoneVerificationCode(User $user): string
    {
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        cache()->put("phone_verification:{$user->id}", $code, CarbonImmutable::now()->addMinutes(10));

        // Implement SMS sending logic here
        // SMS::send($user->phone, "Your verification code is: {$code}");

        $this->log->channel('audit')->$this->logger->info('Phone verification code sent', [
            'user_id' => $user->id,
            'phone' => $user->phone,
        ]);

        return $code;
    }

    /**
     * Accept tenant invitation
     */
    private function acceptInvitation(User $user, string $token): void
    {
        $invitation = TenantInvitation::byToken($token)->pending()->firstOrFail();

        if ($invitation->email !== $user->email) {
            throw ValidationException::withMessages(['invite_code' => ['Invitation email does not match']]);
        }

        if ($invitation->isExpired()) {
            throw ValidationException::withMessages(['invite_code' => ['Invitation has expired']]);
        }

        // Attach user to tenant
        $user->tenants()->attach($invitation->tenant_id, [
            'role' => $invitation->role,
            'is_active' => true,
            'accepted_at' => CarbonImmutable::now(),
        ]);

        // Update user role
        $user->update(['role' => $invitation->role, 'status' => UserStatus::Active]);

        // Mark invitation as accepted
        $invitation->accept();

        $this->log->channel('audit')->$this->logger->info('Tenant invitation accepted', [
            'user_id' => $user->id,
            'tenant_id' => $invitation->tenant_id,
            'role' => $invitation->role->value,
        ]);
    }
}

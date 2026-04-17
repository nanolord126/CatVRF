<?php declare(strict_types=1);

namespace App\Domains\UserProfile\Services;

use App\Domains\UserProfile\DTOs\UpdateProfileDto;
use App\Domains\UserProfile\DTOs\AddAddressDto;
use App\Domains\UserProfile\Models\UserProfile;
use App\Domains\UserProfile\Models\UserAddress;
use App\Services\FraudControlService;
use App\Services\AuditService;
use Illuminate\Database\DatabaseManager;
use Illuminate\Log\LogManager;

final readonly class UserProfileService
{
    public function __construct(
        private readonly DatabaseManager $db,
        private readonly LogManager $logger,
        private readonly AuditService $audit,
        private readonly FraudControlService $fraud,
    ) {}

    /**
     * Update user profile
     */
    public function updateProfile(UpdateProfileDto $dto, string $correlationId): UserProfile
    {
        $this->fraud->check([
            'operation' => 'profile_update',
            'tenant_id' => $dto->tenantId,
            'user_id' => $dto->userId,
            'correlation_id' => $correlationId,
        ]);

        return $this->db->transaction(function () use ($dto, $correlationId) {
            $profile = UserProfile::updateOrCreate(
                [
                    'tenant_id' => $dto->tenantId,
                    'user_id' => $dto->userId,
                ],
                [
                    'first_name' => $dto->firstName,
                    'last_name' => $dto->lastName,
                    'phone' => $dto->phone,
                    'avatar_url' => $dto->avatarUrl,
                    'birth_date' => $dto->birthDate,
                    'gender' => $dto->gender,
                    'preferred_language' => $dto->preferredLanguage,
                    'timezone' => $dto->timezone,
                    'bio' => $dto->bio,
                    'metadata' => $dto->metadata,
                ]
            );

            $this->audit->record(
                action: 'user_profile_updated',
                subjectType: UserProfile::class,
                subjectId: $profile->id,
                newValues: $profile->toArray(),
                correlationId: $correlationId,
            );

            return $profile;
        });
    }

    /**
     * Add or return existing address (max 5)
     */
    public function addOrGetAddress(AddAddressDto $dto, string $correlationId): UserAddress
    {
        $this->fraud->check([
            'operation' => 'address_add',
            'tenant_id' => $dto->tenantId,
            'user_id' => $dto->userId,
            'correlation_id' => $correlationId,
        ]);

        return $this->db->transaction(function () use ($dto, $correlationId) {
            $existing = UserAddress::where([
                'user_id' => $dto->userId,
                'address' => $dto->address,
            ])->first();

            if ($existing) {
                $existing->increment('usage_count');
                return $existing->fresh();
            }

            // Enforce max 5 addresses
            $count = UserAddress::where('user_id', $dto->userId)->count();
            if ($count >= 5) {
                UserAddress::where('user_id', $dto->userId)
                    ->orderBy('usage_count')
                    ->orderBy('id')
                    ->limit(1)
                    ->delete();
            }

            // If setting as default, remove default from others
            if ($dto->isDefault) {
                UserAddress::where('user_id', $dto->userId)
                    ->update(['is_default' => false]);
            }

            $address = UserAddress::create([
                'tenant_id' => $dto->tenantId,
                'user_id' => $dto->userId,
                'address' => $dto->address,
                'type' => $dto->type,
                'city' => $dto->city,
                'region' => $dto->region,
                'postal_code' => $dto->postalCode,
                'country' => $dto->country,
                'lat' => $dto->lat,
                'lon' => $dto->lon,
                'is_default' => $dto->isDefault,
                'usage_count' => 1,
            ]);

            $this->audit->record(
                action: 'user_address_added',
                subjectType: UserAddress::class,
                subjectId: $address->id,
                newValues: $address->toArray(),
                correlationId: $correlationId,
            );

            return $address;
        });
    }

    /**
     * Delete address
     */
    public function deleteAddress(int $tenantId, int $userId, int $addressId, string $correlationId): bool
    {
        return $this->db->transaction(function () use ($tenantId, $userId, $addressId, $correlationId) {
            $address = UserAddress::where('id', $addressId)
                ->where('tenant_id', $tenantId)
                ->where('user_id', $userId)
                ->first();

            if (!$address) {
                return false;
            }

            $address->delete();

            $this->audit->record(
                action: 'user_address_deleted',
                subjectType: UserAddress::class,
                subjectId: $addressId,
                correlationId: $correlationId,
            );

            return true;
        });
    }

    /**
     * Set default address
     */
    public function setDefaultAddress(int $tenantId, int $userId, int $addressId, string $correlationId): bool
    {
        return $this->db->transaction(function () use ($tenantId, $userId, $addressId, $correlationId) {
            // Remove default from all addresses
            UserAddress::where('user_id', $userId)
                ->update(['is_default' => false]);

            // Set new default
            $updated = UserAddress::where('id', $addressId)
                ->where('tenant_id', $tenantId)
                ->where('user_id', $userId)
                ->update(['is_default' => true]);

            if ($updated) {
                $this->audit->record(
                    action: 'user_address_set_default',
                    subjectType: UserAddress::class,
                    subjectId: $addressId,
                    correlationId: $correlationId,
                );
            }

            return $updated > 0;
        });
    }

    /**
     * Get user profile
     */
    public function getProfile(int $tenantId, int $userId): ?UserProfile
    {
        return UserProfile::where('tenant_id', $tenantId)
            ->where('user_id', $userId)
            ->first();
    }

    /**
     * Get user addresses
     */
    public function getAddresses(int $tenantId, int $userId): \Illuminate\Database\Eloquent\Collection
    {
        return UserAddress::where('tenant_id', $tenantId)
            ->where('user_id', $userId)
            ->orderBy('is_default', 'desc')
            ->orderBy('usage_count', 'desc')
            ->get();
    }
}

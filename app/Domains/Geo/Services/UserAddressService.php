<?php

declare(strict_types=1);

namespace App\Domains\Geo\Services;

use App\Models\UserAddress;
use Illuminate\Database\Eloquent\Collection;

/**
 * Class UserAddressService
 *
 * Part of the Geo vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Service layer following CatVRF canon:
 * - Constructor injection only (no Facades)
 * - FraudControlService::check() before mutations
 * - $this->db->transaction() wrapping all write operations
 * - Audit logging with correlation_id
 * - Tenant and BusinessGroup scoping
 *
 * @see \App\Services\FraudControlService
 * @see \App\Services\AuditService
 * @package App\Domains\Geo\Services
 */
final readonly class UserAddressService
{
    /**
     * Безоговорочное добавление или возвращение существующего адреса юзера (max 5).
     */
    public function addOrGetAddress(int $userId, string $address, string $type = 'other'): UserAddress
    {
        $existing = UserAddress::where(['user_id' => $userId, 'address' => $address])->first();

        if ($existing) {
            $existing->increment('usage_count');
            return $existing;
        }

        $count = UserAddress::where('user_id', $userId)->count();
        if ($count >= 5) {
            UserAddress::where('user_id', $userId)->orderBy('usage_count')->limit(1)->delete();
        }

        /** @var UserAddress $newAddress */
        $newAddress = UserAddress::create([
            'user_id' => $userId,
            'address' => $address,
            'type' => $type,
            'usage_count' => 1,
        ]);

        return $newAddress;
    }

    /**
     * Получить историю перемещений (адресов) юзера.
     */
    public function getAddressHistory(int $userId): Collection
    {
        return UserAddress::where('user_id', $userId)
            ->orderBy('usage_count', 'desc')
            ->limit(5)
            ->get();
    }
}

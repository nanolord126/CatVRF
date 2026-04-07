<?php declare(strict_types=1);

namespace App\Services;

use App\Models\UserAddress;
use Illuminate\Support\Collection;


use Illuminate\Support\Str;
use App\Services\FraudControlService;
use Illuminate\Log\LogManager;
use Illuminate\Database\DatabaseManager;

final readonly class UserAddressService
{
    public function __construct(
        private readonly LogManager $logger,
        private readonly DatabaseManager $db,
    ) {}

    /**
         * Добавить или вернуть существующий адрес (максимум 5)
         */
        public function addOrGetAddress(int $userId, string $address, string $type = 'other'): UserAddress
        {
            $correlationId = Str::uuid()->toString();

            $this->fraud->check(new \stdClass());

            return $this->db->transaction(function () use ($userId, $address, $type, $correlationId): UserAddress {
                $existing = UserAddress::query()->where([
                    'user_id' => $userId,
                    'address' => $address,
                ])->first();

                if ($existing instanceof UserAddress) {
                    $existing->increment('usage_count');

                    $this->logger->channel('audit')->info('User address reused', [
                        'user_id' => $userId,
                        'address_id' => $existing->id,
                        'correlation_id' => $correlationId,
                    ]);

                    return $existing->refresh();
                }

                $count = UserAddress::query()->where('user_id', $userId)->count();
                if ($count >= 5) {
                    UserAddress::query()->where('user_id', $userId)
                        ->orderBy('usage_count')
                        ->orderBy('id')
                        ->limit(1)
                        ->delete();
                }

                $created = UserAddress::query()->create([
                    'user_id' => $userId,
                    'address' => $address,
                    'type' => $type,
                    'usage_count' => 1,
                ]);

                $this->logger->channel('audit')->info('User address created', [
                    'user_id' => $userId,
                    'address_id' => $created->id,
                    'correlation_id' => $correlationId,
                ]);

                return $created;
            });
        }

        /**
         * Получить историю поездок/доставок пользователя
         */
        public function getAddressHistory(int $userId): Collection
        {
            return UserAddress::where('user_id', $userId)
                ->orderBy('usage_count', 'desc')
                ->limit(5)
                ->get();
        }
}

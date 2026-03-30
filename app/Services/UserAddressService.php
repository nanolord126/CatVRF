<?php declare(strict_types=1);

namespace App\Services;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class UserAddressService extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    /**
         * Добавить или вернуть существующий адрес (максимум 5)
         */
        public function addOrGetAddress(int $userId, string $address, string $type = 'other'): UserAddress
        {
            // Проверить, существует ли уже такой адрес
            $existing = UserAddress::where([
                'user_id' => $userId,
                'address' => $address,
            ])->first();

            if ($existing) {
                $existing->increment('usage_count');
                return $existing;
            }

            // Проверить, не превышены ли 5 адресов
            $count = UserAddress::where('user_id', $userId)->count();
            if ($count >= 5) {
                // Удалить наименее используемый адрес
                UserAddress::where('user_id', $userId)
                    ->orderBy('usage_count')
                    ->limit(1)
                    ->delete();
            }

            // Создать новый адрес
            return UserAddress::create([
                'user_id' => $userId,
                'address' => $address,
                'type' => $type,
                'usage_count' => 1,
            ]);
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

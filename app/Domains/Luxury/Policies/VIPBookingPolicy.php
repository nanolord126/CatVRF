<?php declare(strict_types=1);

namespace App\Domains\Luxury\Policies;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class VIPBookingPolicy extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use HandlesAuthorization;

        public function __construct(
            private readonly FraudControlService $fraudControl
        ) {}

        /**
         * Просмотр бронирований
         */
        public function view(User $user, VIPBooking $booking): bool
        {
            // Только владелец (клиент) или консьерж (сотрудник)
            return $user->id === $booking->client->user_id || $user->hasRole('concierge');
        }

        /**
         * Создание бронирований
         */
        public function create(User $user): bool
        {
            // Проверка фрод-контроля для VIP
            try {
                $this->fraudControl->check([
                    'user_id' => $user->id,
                    'operation' => 'create_vip_booking_policy',
                    'correlation_id' => bin2hex(random_bytes(16)),
                ]);
            } catch (\Throwable $e) {
                return false;
            }

            return true;
        }

        /**
         * Отмена бронирования
         */
        public function update(User $user, VIPBooking $booking): bool
        {
            // Отмена допустима за 24 часа до брони для Gold или в любое время для Black VIP
            $client = $booking->client;

            if ($client->vip_level === 'black') {
                return true;
            }

            if ($booking->booking_at->diffInHours(now()) < 24) {
                return $user->hasRole('admin');
            }

            return $user->id === $client->user_id;
        }
}

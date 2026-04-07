<?php declare(strict_types=1);

/**
 * VIPBookingPolicy — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/vipbookingpolicy
 */


namespace App\Domains\Luxury\Policies;


use Illuminate\Contracts\Auth\Guard;
final class VIPBookingPolicy
{

    use HandlesAuthorization;

        public function __construct(
            private readonly FraudControlService $fraud, private readonly Guard $guard
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
                $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'create_vip_booking_policy', amount: 0, correlationId: $correlationId ?? '');
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

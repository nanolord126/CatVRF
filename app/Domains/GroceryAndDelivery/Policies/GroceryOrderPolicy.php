<?php declare(strict_types=1);

namespace App\Domains\GroceryAndDelivery\Policies;

use Carbon\Carbon;


use Psr\Log\LoggerInterface;
final class GroceryOrderPolicy
{

    use HandlesAuthorization;

        public function __construct(
            private readonly FraudControlService $fraudService, private readonly LoggerInterface $logger) {}

        /**
         * Проверка, может ли пользователь просматривать заказ
         */
        public function view(User $user, GroceryOrder $order): bool
        {
            $canView = $user->id === $order->user_id || $user->isTenantAdmin();

            $this->logger->info('GroceryOrderPolicy::view checked', [
                'user_id' => $user->id,
                'order_id' => $order->id,
                'can_view' => $canView,
                'correlation_id' => $order->correlation_id,
            ]);

            return $canView;
        }

        /**
         * Проверка, может ли пользователь обновлять заказ
         */
        public function update(User $user, GroceryOrder $order): bool
        {
            // Можно обновлять только свои заказы, и только если они не отправлены
            $canUpdate = $user->id === $order->user_id
                && in_array($order->status, ['pending', 'confirmed']);

            // Проверка фрода перед разрешением изменений
            if ($canUpdate) {
                $fraudScore = $this->fraudService->scoreOperation(
                    operationType: 'order_modification',
                    userId: $user->id,
                    amount: $order->total_price,
                    correlationId: $order->correlation_id
                );

                if ($fraudScore > 0.75) {
                    $this->logger->warning('Order modification blocked by fraud score', [
                        'user_id' => $user->id,
                        'order_id' => $order->id,
                        'fraud_score' => $fraudScore,
                        'correlation_id' => $order->correlation_id,
                    ]);
                    return false;
                }
            }

            $this->logger->info('GroceryOrderPolicy::update checked', [
                'user_id' => $user->id,
                'order_id' => $order->id,
                'status' => $order->status,
                'can_update' => $canUpdate,
                'correlation_id' => $order->correlation_id,
            ]);

            return $canUpdate;
        }

        /**
         * Проверка, может ли пользователь отменить заказ
         */
        public function cancel(User $user, GroceryOrder $order): bool
        {
            // Можно отменить только свои заказы до доставки
            $canCancel = $user->id === $order->user_id
                && in_array($order->status, ['pending', 'confirmed', 'in_transit']);

            // Проверка фрода - частые отмены могут быть абусом
            if ($canCancel) {
                $cancelCount = GroceryOrder::where('user_id', $user->id)
                    ->where('status', 'cancelled')
                    ->where('created_at', '>=', Carbon::now()->subHours(24))
                    ->count();

                if ($cancelCount > 10) {
                    $this->logger->warning('User cancel limit exceeded', [
                        'user_id' => $user->id,
                        'cancel_count_24h' => $cancelCount,
                        'correlation_id' => $order->correlation_id,
                    ]);
                    return false;
                }
            }

            $this->logger->info('GroceryOrderPolicy::cancel checked', [
                'user_id' => $user->id,
                'order_id' => $order->id,
                'status' => $order->status,
                'can_cancel' => $canCancel,
                'correlation_id' => $order->correlation_id,
            ]);

            return $canCancel;
        }

        /**
         * Проверка, может ли пользователь удалить заказ
         */
        public function delete(User $user, GroceryOrder $order): bool
        {
            // Удалять могут только админы бизнеса (после 30 дней или для delivered)
            $canDelete = $user->isTenantAdmin() && (
                $order->status === 'delivered'
                || $order->created_at->addDays(30)->isPast()
            );

            $this->logger->info('GroceryOrderPolicy::delete checked', [
                'user_id' => $user->id,
                'order_id' => $order->id,
                'is_admin' => $user->isTenantAdmin(),
                'can_delete' => $canDelete,
                'correlation_id' => $order->correlation_id,
            ]);

            return $canDelete;
        }

        /**
         * Проверка, может ли пользователь подтвердить заказ
         */
        public function confirm(User $user, GroceryOrder $order): bool
        {
            // Подтверждать могут админы магазина или система
            $isStoreAdmin = $user->id === $order->store->owner_id || $user->isTenantAdmin();
            $canConfirm = $isStoreAdmin && $order->status === 'pending';

            $this->logger->info('GroceryOrderPolicy::confirm checked', [
                'user_id' => $user->id,
                'order_id' => $order->id,
                'is_store_admin' => $isStoreAdmin,
                'can_confirm' => $canConfirm,
                'correlation_id' => $order->correlation_id,
            ]);

            return $canConfirm;
        }

        /**
         * Проверка, может ли пользователь создавать заказы (rate limit)
         */
        public function create(User $user): bool
        {
            // Rate limit: максимум 10 заказов в час
            $recentOrderCount = GroceryOrder::where('user_id', $user->id)
                ->where('created_at', '>=', Carbon::now()->subHour())
                ->count();

            $canCreate = $recentOrderCount < 10;

            if (!$canCreate) {
                $this->logger->warning('User order creation rate limited', [
                    'user_id' => $user->id,
                    'orders_in_hour' => $recentOrderCount,
                ]);
            }

            return $canCreate;
        }
}

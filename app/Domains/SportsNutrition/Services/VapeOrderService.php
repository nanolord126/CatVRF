<?php declare(strict_types=1);

namespace App\Domains\SportsNutrition\Services;

use App\Domains\SportsNutrition\Models\VapeOrder;
use App\Services\FraudControlService;
use App\Domains\Wallet\Services\WalletService;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;
final readonly class VapeOrderService
{


    /**
         * Конструктор с DP.
         */
        public function __construct(private VapeAgeVerificationService $ageVerifier,
            private WalletService $wallet,
            private FraudControlService $fraud,
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger, private readonly Guard $guard) {}

        /**
         * Создать черновик заказа (Draft) с 18+ проверкой.
         */
        public function createOrder(int $userId, array $params, string $correlationId = null): VapeOrder
        {
            $correlationId ??= (string) Str::uuid();

            $this->logger->info('Vape order init request', [
                'user_id' => $userId,
                'params' => $params,
                'correlation_id' => $correlationId,
            ]);

            // 1. Жёсткая проверка на 18+ перед созданием заказа (Возрастной Гейт)
            if (!$this->ageVerifier->hasAValidVerification($userId)) {
                $this->logger->warning('Vape order rejected: No age verification', [
                    'user_id' => $userId,
                    'correlation_id' => $correlationId,
                ]);
                throw new \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException(
                    'Age verification Required (18+) via ESIA/EBS/ID',
                );
            }

            // 2. Fraud Check попытки заказа
            $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'vape_order_create', amount: 0, correlationId: $correlationId ?? '');

            return $this->db->transaction(function () use ($userId, $params, $correlationId) {

                // 3. Создаем запись заказа ( Draft )
                $order = VapeOrder::create([
                    'user_id' => $userId,
                    'status' => 'pending',
                    'amount_kopecks' => $params['amount_kopecks'] ?? 0,
                    'items_count' => count($params['items'] ?? []),
                    'items_summary' => $params['items'] ?? [],
                    'correlation_id' => $correlationId,
                    'marking_session_id' => (string) Str::uuid(), // Инициируем сессию "Честный ЗНАК"
                ]);

                // 4. Резервируем товар (Hold) — логика реализована будет в InventoryService
                // Здесь фиксируем факт начала резервации.
                $this->logger->info('Vape inventory hold started', [
                    'order_id' => $order->id,
                    'correlation_id' => $correlationId,
                ]);

                return $order;
            });
        }

        /**
         * Подтверждение оплаты и финализация заказа.
         * После этого этапа запуск "Честного ЗНАКа" (КИЗ/ОФД).
         */
        public function completeOrder(string $orderUuid, string $correlationId = null): bool
        {
            $correlationId ??= (string) Str::uuid();

            return $this->db->transaction(function () use ($orderUuid, $correlationId) {

                $order = VapeOrder::where('uuid', $orderUuid)->lockForUpdate()->firstOrFail();

                if ($order->status !== 'pending') {
                    return false;
                }

                // 5. Оплачиваем через Wallet (если есть на счету)
                $this->wallet->debit($order->amount_kopecks, 'vape_purchase', $order->user_id, $correlationId);

                // 6. Переводим в 'paid' - статус готовности к отгрузке (выбытие КИЗ)
                $order->update([
                    'status' => 'paid',
                    'correlation_id' => $correlationId,
                ]);

                return true;
            });
        }

        /**
         * Отмена заказа с возвратом средств в Wallet.
         */
        public function cancelOrder(string $orderUuid, string $reason, string $correlationId = null): bool
        {
            $correlationId ??= (string) Str::uuid();

            return $this->db->transaction(function () use ($orderUuid, $reason, $correlationId) {

                $order = VapeOrder::where('uuid', $orderUuid)->lockForUpdate()->firstOrFail();

                if ($order->status === 'cancelled') {
                    return false;
                }

                // 7. Если оплачен — возврат (Refund) в Wallet
                if ($order->status === 'paid') {
                    $this->wallet->credit($order->amount_kopecks, 'vape_refund', $order->user_id, $correlationId);
                }

                $order->update([
                    'status' => 'cancelled',
                    'reason' => $reason,
                    'correlation_id' => $correlationId,
                ]);

                return true;
            });
        }
}

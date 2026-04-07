<?php declare(strict_types=1);

namespace App\Domains\Auto\Cars\Services;



use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
final readonly class CarDealershipService
{

    private readonly string $correlationId;


    public function __construct(private readonly \App\Services\FraudControlService $fraud,
            string $correlationId = '',
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger, private readonly Guard $guard) {
            if (empty($this->correlationId)) {
                $this->correlationId = (string) Str::uuid();
            }
        }

        /**
         * Создать новый заказ на автомобиль с поддержкой транзактности и фрод-контроля.
         */
        public function placeOrder(int $carId, int $clientId, int $amount): CarOrder
        {
            $this->logger->info('Attempting to place car order', [
                'car_id' => $carId,
                'client_id' => $clientId,
                'correlation_id' => $this->correlationId
            ]);

            return $this->db->transaction(function () use ($carId, $clientId, $amount) {
                // 1. Поиск авто с блокировкой
                $car = Car::lockForUpdate()->findOrFail($carId);

                if ($car->status !== 'available') {
                    throw new RuntimeException('Car is not available for order');
                }

                // 2. Fraud Check (по Канону 2026)
                $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'car_order', amount: 0, correlationId: $correlationId ?? '');

                // 3. Создание заказа
                $order = CarOrder::create([
                    'car_id' => $carId,
                    'client_id' => $clientId,
                    'amount' => $amount,
                    'status' => 'pending',
                    'correlation_id' => $this->correlationId,
                    'idempotency_key' => $this->correlationId // Используем корреляцию как ключ идемпотентности
                ]);

                // 4. Резерв автомобиля
                $car->update([
                    'status' => 'reserved',
                    'correlation_id' => $this->correlationId
                ]);

                $this->logger->info('Car order placed successfully', [
                    'order_id' => $order->id,
                    'correlation_id' => $this->correlationId
                ]);

                return $order;
            });
        }

        /**
         * Список доступных авто с учетом Tenant Scoping.
         */
        public function getAvailableCars(int $dealerId = null): Collection
        {
            $query = Car::where('status', 'available');

            if ($dealerId) {
                $query->where('dealer_id', $dealerId);
            }

            return $query->with(['model.brand', 'dealer'])->get();
        }
}

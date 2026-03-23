<?php declare(strict_types=1);

namespace App\Domains\Cars\Services;

use App\Domains\Cars\Models\Car;
use App\Domains\Cars\Models\CarOrder;
use App\Services\FraudControlService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

final class CarDealershipService
{
    public function __construct(
        private readonly string $correlationId = ''
    ) {
        if (empty($this->correlationId)) {
            $this->correlationId = (string) Str::uuid();
        }
    }

    /**
     * Создать новый заказ на автомобиль с поддержкой транзактности и фрод-контроля.
     */
    public function placeOrder(int $carId, int $clientId, int $amount): CarOrder
    {
        Log::channel('audit')->info('Attempting to place car order', [
            'car_id' => $carId,
            'client_id' => $clientId,
            'correlation_id' => $this->correlationId
        ]);

        return DB::transaction(function () use ($carId, $clientId, $amount) {
            // 1. Поиск авто с блокировкой
            $car = Car::lockForUpdate()->findOrFail($carId);

            if ($car->status !== 'available') {
                throw new RuntimeException('Car is not available for order');
            }

            // 2. Fraud Check (по Канону 2026)
            FraudControlService::check([
                'type' => 'car_order',
                'amount' => $amount,
                'client_id' => $clientId,
                'correlation_id' => $this->correlationId
            ]);

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

            Log::channel('audit')->info('Car order placed successfully', [
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
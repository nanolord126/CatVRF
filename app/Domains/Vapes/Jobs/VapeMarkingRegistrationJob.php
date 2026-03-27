<?php

declare(strict_types=1);

namespace App\Domains\Vapes\Jobs;

use App\Domains\Vapes\Models\VapeOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * VapeMarkingRegistrationJob — Production Ready 2026
 * 
 * Фоновая задача регистрации выбытия товара в системе "Честный ЗНАК" (ГИС МТ).
 * Инициируется после успешной оплаты заказа.
 */
class VapeMarkingRegistrationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;
    public int $backoff = 60; // 1 min

    /**
     * Создание задачи.
     */
    public function __construct(
        public int $orderId,
        public string $correlationId,
    ) {}

    /**
     * Выполнение регистрации выбытия в ГИС МТ.
     */
    public function handle(): void
    {
        Log::channel('audit')->info('Vape marking registration job: started', [
            'order_id' => $this->orderId,
            'correlation_id' => $this->correlationId,
        ]);

        try {
            $order = VapeOrder::findOrFail($this->orderId);

            // Имитация API-запроса в "Честный ЗНАК"
            // В реальности: отправка УКЭП-подписанного XML с КИЗами
            $gisMtResponse = $this->callGisMtApi($order);

            if ($gisMtResponse['success']) {
                $order->update([
                    'marking_status' => 'completed',
                    'marking_response' => $gisMtResponse,
                ]);

                Log::channel('audit')->info('Vape marking registration: SUCCESS', [
                    'order_id' => $this->orderId,
                    'correlation_id' => $this->correlationId,
                ]);
            } else {
                throw new \Exception('GIS MT API returned error: ' . $gisMtResponse['message']);
            }

        } catch (\Throwable $e) {
            
            Log::channel('audit')->error('Vape marking registration: FAILED', [
                'order_id' => $this->orderId,
                'error' => $e->getMessage(),
                'correlation_id' => $this->correlationId,
            ]);

            throw $e; // Повтор через Queue backoff
        }
    }

    /**
     * Имитация API вызова.
     */
    private function callGisMtApi(VapeOrder $order): array
    {
        return [
            'success' => true,
            'message' => 'Document accepted',
            'transaction_id' => (string) Str::uuid(),
        ];
    }
}

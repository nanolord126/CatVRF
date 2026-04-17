<?php declare(strict_types=1);

namespace App\Domains\SportsNutrition\Jobs;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;

use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\SerializesModels;


use Psr\Log\LoggerInterface;
final class VapeMarkingRegistrationJob
{


    use \Illuminate\Foundation\Bus\Dispatchable, \Illuminate\Queue\InteractsWithQueue, \Illuminate\Bus\Queueable, \Illuminate\Queue\SerializesModels;

        public int $tries = 5;
        public int $backoff = 60; // 1 min

        /**
         * Создание задачи.
         */
        public function __construct(
            public int $orderId,
            public string $correlationId, private readonly LoggerInterface $logger) {}

        /**
         * Выполнение регистрации выбытия в ГИС МТ.
         */
        public function handle(): void
        {
            $this->logger->info('Vape marking registration job: started', [
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

                    $this->logger->info('Vape marking registration: SUCCESS', [
                        'order_id' => $this->orderId,
                        'correlation_id' => $this->correlationId,
                    ]);
                } else {
                    throw new \RuntimeException('GIS MT API returned error: ' . $gisMtResponse['message']);
                }

            } catch (\Throwable $e) {

                $this->logger->error('Vape marking registration: FAILED', [
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


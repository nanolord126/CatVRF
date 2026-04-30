<?php declare(strict_types=1);

namespace App\Domains\HealthAndSports\SubVerticals\SportsNutrition\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use Psr\Log\LoggerInterface;
use Illuminate\Support\Str;

final class VapeMarkingRegistrationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public array $backoff = [60, 300, 900];
    public int $timeout = 120;
    public int $tries = 5;

    public function __construct(
        public int $orderId,
        public string $correlationId,
        private readonly LoggerInterface $logger,
    ) {}

    public function tags(): array
    {
        return ['sportsnutrition', 'job'];
    }

    public function handle(): void
    {
        $this->logger->info('Vape marking registration job: started', [
            'order_id' => $this->orderId,
            'correlation_id' => $this->correlationId,
        ]);

        try {
            $order = VapeOrder::findOrFail($this->orderId);

            // Имитация API-запроса в "Честный ЗНАК"
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

            throw $e;
        }
    }

    private function callGisMtApi(VapeOrder $order): array
    {
        return [
            'success' => true,
            'message' => 'Document accepted',
            'transaction_id' => (string) Str::uuid(),
        ];
    }

    public function failed(\Throwable $exception): void
    {
        $this->logger->error('sportsnutrition job failed', [
            'error' => $exception->getMessage(),
        ]);
    }
}
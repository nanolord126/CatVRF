<?php

declare(strict_types=1);

namespace App\Domains\Supermarket\Jobs;

use App\Domains\Supermarket\Adapters\ColdChainAdapter;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Psr\Log\LoggerInterface;

/**
 * ColdChainMonitorJob - мониторинг холодовой цепи для заказов Supermarket.
 *
 * Запускается по расписанию для проверки температурного режима
 * и уведомления о нарушениях.
 */
final readonly class ColdChainMonitorJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;
    public int $timeout = 120;

    public function __construct(
        private readonly ColdChainAdapter $coldChainAdapter,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->logger->info('Cold chain monitoring job started');

        try {
            $this->monitorActiveOrders();
            $this->checkExpiredOrders();
            $this->cleanupOldData();

            $this->logger->info('Cold chain monitoring job completed');
        } catch (\Exception $e) {
            $this->logger->error('Cold chain monitoring job failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Мониторинг активных заказов.
     */
    private function monitorActiveOrders(): void
    {
        $activeOrders = \DB::table('supermarket_cold_chain_monitoring')
            ->where('status', 'monitoring')
            ->where('last_check_at', '<', now()->subMinutes(5))
            ->get();

        foreach ($activeOrders as $order) {
            try {
                $status = $this->coldChainAdapter->getColdChainStatus($order->order_id);

                // Обновление времени последней проверки
                \DB::table('supermarket_cold_chain_monitoring')
                    ->where('id', $order->id)
                    ->update([
                        'last_check_at' => now(),
                        'updated_at' => now(),
                    ]);

                // Если есть нарушения, логируем
                if ($status['violations_count'] > 0) {
                    $this->logger->warning('Cold chain violations detected', [
                        'order_id' => $order->order_id,
                        'violations_count' => $status['violations_count'],
                        'last_temperature' => $status['last_temperature'],
                    ]);

                    // Здесь можно добавить отправку уведомлений
                    // event(new ColdChainViolationDetected($order->order_id, $status));
                }

                $this->logger->info('Cold chain monitored', [
                    'order_id' => $order->order_id,
                    'status' => $status['status'],
                    'violations_count' => $status['violations_count'],
                ]);
            } catch (\Exception $e) {
                $this->logger->error('Failed to monitor order cold chain', [
                    'order_id' => $order->order_id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Проверка заказов с истекшим временем доставки.
     */
    private function checkExpiredOrders(): void
    {
        $expiredOrders = \DB::table('supermarket_cold_chain_monitoring')
            ->where('status', 'monitoring')
            ->where('last_check_at', '<', now()->subHours(4))
            ->get();

        foreach ($expiredOrders as $order) {
            try {
                // Автоматическое завершение мониторинга для старых заказов
                $this->coldChainAdapter->unregisterOrderFromMonitoring($order->order_id);

                $this->logger->info('Cold chain monitoring expired and stopped', [
                    'order_id' => $order->order_id,
                ]);
            } catch (\Exception $e) {
                $this->logger->error('Failed to expire order cold chain monitoring', [
                    'order_id' => $order->order_id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Очистка старых данных.
     */
    private function cleanupOldData(): void
    {
        // Удаление температурных данных старше 7 дней
        $deletedTemps = \DB::table('supermarket_cold_chain_temperatures')
            ->where('recorded_at', '<', now()->subDays(7))
            ->delete();

        // Удаление записей мониторинга старше 30 дней
        $deletedMonitoring = \DB::table('supermarket_cold_chain_monitoring')
            ->whereIn('status', ['completed', 'expired'])
            ->where('updated_at', '<', now()->subDays(30))
            ->delete();

        $this->logger->info('Cold chain data cleaned up', [
            'deleted_temperatures' => $deletedTemps,
            'deleted_monitoring' => $deletedMonitoring,
        ]);
    }
}

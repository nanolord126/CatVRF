<?php declare(strict_types=1);

namespace Modules\Payments\Jobs;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class BatchPayoutJob extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use InteractsWithQueue, Queueable, SerializesModels;
    
        public int $timeout = 3600;
        public int $tries = 3;
        public array $tags = ['payment', 'batch-payout'];
    
        public function __construct(
            private string $batchId,
            private string $correlationId = '',
        ) {}
    
        public function handle(MassPayoutService $payoutService): void
        {
            try {
                Log::channel('audit')->info('BatchPayoutJob запущена', [
                    'batch_id' => $this->batchId,
                    'correlation_id' => $this->correlationId,
                ]);
    
                // Получаем все платежи в batch
                $payments = PaymentTransaction::where('batch_id', $this->batchId)
                    ->where('status', 'pending')
                    ->get();
    
                foreach ($payments as $payment) {
                    try {
                        $payoutService->executePayout(
                            $payment->id,
                            $payment->gateway ?? 'tinkoff',
                            $this->correlationId,
                        );
    
                        // Задержка между платежами (anti-DDoS)
                        sleep(5);
                    } catch (Exception $e) {
                        Log::channel('audit')->error('Ошибка при выплате в batch', [
                            'payment_id' => $payment->id,
                            'batch_id' => $this->batchId,
                            'error' => $e->getMessage(),
                            'correlation_id' => $this->correlationId,
                        ]);
    
                        // Отмечаем как failed
                        $payment->update(['status' => 'failed']);
    
                        // Не прерываем batch — продолжаем дальше
                    }
                }
    
                Log::channel('audit')->info('BatchPayoutJob завершена', [
                    'batch_id' => $this->batchId,
                    'correlation_id' => $this->correlationId,
                ]);
            } catch (Exception $e) {
                Log::channel('audit')->error('Критическая ошибка BatchPayoutJob', [
                    'batch_id' => $this->batchId,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'correlation_id' => $this->correlationId,
                ]);
    
                throw $e;
            }
        }
    
        public function failed(Exception $exception): void
        {
            Log::channel('audit')->error('BatchPayoutJob failed', [
                'batch_id' => $this->batchId,
                'error' => $exception->getMessage(),
                'correlation_id' => $this->correlationId,
            ]);
        }
}

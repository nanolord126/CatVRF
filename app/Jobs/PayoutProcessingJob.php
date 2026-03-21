<?php declare(strict_types=1);

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Modules\Finances\Models\WithdrawalRequest;
use Modules\Finances\Services\PaymentGateway\PaymentGatewayInterface;
use Modules\Finances\Services\FraudMLService;
use Illuminate\Support\Str;

/**
 * Payout Processing Job
 * CANON 2026 - Production Ready
 *
 * Ежедневная обработка выводов денег.
 * Пакетная отправка выводов на платёжные системы.
 * Запускается каждый день в 22:00 UTC.
 */
final class PayoutProcessingJob implements ShouldQueue
{
    use Dispatchable, Queueable, InteractsWithQueue, SerializesModels;

    public int $timeout = 3600; // 1 час
    public int $tries = 3;
    public int $backoff = 300; // 5 минут между попытками

    private readonly PaymentGatewayInterface $gateway;
    private readonly FraudMLService $fraudMLService;
    private readonly string $correlationId;

    public function __construct()
    {
        $this->gateway = app(PaymentGatewayInterface::class);
        $this->fraudMLService = app(FraudMLService::class);
        $this->correlationId = (string) Str::uuid();
    }

    public function handle(): void
    {
        try {
            Log::channel('audit')->info('Payout processing started', [
                'correlation_id' => $this->correlationId,
                'timestamp' => now()->toIso8601String(),
            ]);

            // 1. Найти все pending выводы
            $pendingPayouts = WithdrawalRequest::query()
                ->where('status', 'pending')
                ->where('scheduled_for', '<=', now())
                ->lockForUpdate()
                ->get();

            if ($pendingPayouts->isEmpty()) {
                Log::info('No pending payouts to process');
                return;
            }

            Log::info('Processing payouts', [
                'correlation_id' => $this->correlationId,
                'count' => $pendingPayouts->count(),
            ]);

            // 2. Проверить фрод на каждом выводе
            $validPayouts = [];
            foreach ($pendingPayouts as $payout) {
                if ($this->checkFraud($payout)) {
                    $validPayouts[] = $payout;
                } else {
                    $this->markAsFraudulent($payout);
                }
            }

            if (empty($validPayouts)) {
                Log::warning('All payouts marked as fraudulent', [
                    'correlation_id' => $this->correlationId,
                ]);
                return;
            }

            // 3. Группировать по способам вывода (СБП, карта, банк)
            $payoutsByMethod = $this->groupByMethod($validPayouts);

            // 4. Обработать каждый метод
            $successCount = 0;
            $failureCount = 0;

            foreach ($payoutsByMethod as $method => $payouts) {
                foreach ($payouts as $payout) {
                    try {
                        $this->processPayout($payout, $method);
                        $successCount++;
                    } catch (\Exception $e) {
                        $failureCount++;
                        Log::warning('Payout processing failed', [
                            'correlation_id' => $this->correlationId,
                            'payout_id' => $payout->id,
                            'error' => $e->getMessage(),
                        ]);

                        // Переместить в retry очередь
                        $this->retryPayout($payout);
                    }
                }
            }

            Log::channel('audit')->info('Payout processing completed', [
                'correlation_id' => $this->correlationId,
                'successful' => $successCount,
                'failed' => $failureCount,
            ]);

        } catch (\Exception $e) {
            Log::channel('audit')->error('Payout processing job failed', [
                'correlation_id' => $this->correlationId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Проверить фрод на выводе
     */
    private function checkFraud(WithdrawalRequest $payout): bool
    {
        $fraudScore = $this->fraudMLService->scoreOperation(
            operation_type: 'payout',
            user_id: $payout->user_id,
            amount: $payout->amount,
            ip_address: $payout->ip_address ?? request()->ip(),
            device_fingerprint: $payout->device_fingerprint,
        );

        return $fraudScore < (float) config('fraud.thresholds.block_score', 0.85);
    }

    /**
     * Отметить вывод как мошеннический
     */
    private function markAsFraudulent(WithdrawalRequest $payout): void
    {
        DB::transaction(function () use ($payout) {
            $payout->update([
                'status' => 'rejected',
                'rejection_reason' => 'Fraud detection',
                'rejected_at' => now(),
                'correlation_id' => $this->correlationId,
            ]);

            // Вернуть деньги на кошелёк
            $payout->wallet->refund($payout->amount, 'Payout rejected due to fraud');

            Log::warning('Payout marked as fraudulent', [
                'payout_id' => $payout->id,
            ]);
        });
    }

    /**
     * Группировать выводы по способам
     */
    private function groupByMethod(array $payouts): array
    {
        $grouped = [];

        foreach ($payouts as $payout) {
            $method = $payout->method; // sbp, card, bank_transfer
            if (!isset($grouped[$method])) {
                $grouped[$method] = [];
            }
            $grouped[$method][] = $payout;
        }

        return $grouped;
    }

    /**
     * Обработать конкретный вывод
     */
    private function processPayout(WithdrawalRequest $payout, string $method): void
    {
        DB::transaction(function () use ($payout, $method) {
            $payout->update([
                'status' => 'processing',
                'processing_started_at' => now(),
                'correlation_id' => $this->correlationId,
            ]);

            // Вызвать платёжный шлюз
            $result = $this->gateway->createPayout(
                amount: $payout->amount,
                method: $method,
                recipient: $payout->recipient_data,
                idempotency_key: $payout->idempotency_key,
            );

            if (!$result->isSuccessful()) {
                throw new \Exception("Payout gateway error: {$result->getMessage()}");
            }

            // Обновить статус
            $payout->update([
                'status' => 'sent',
                'sent_at' => now(),
                'provider_transaction_id' => $result->getTransactionId(),
                'correlation_id' => $this->correlationId,
            ]);

            Log::info('Payout sent successfully', [
                'payout_id' => $payout->id,
                'provider_id' => $result->getTransactionId(),
            ]);
        });
    }

    /**
     * Переместить вывод в retry очередь
     */
    private function retryPayout(WithdrawalRequest $payout): void
    {
        $payout->update([
            'retry_count' => ($payout->retry_count ?? 0) + 1,
            'last_retry_at' => now(),
        ]);

        if (($payout->retry_count ?? 0) >= 3) {
            $payout->update(['status' => 'failed']);
            $payout->wallet->refund($payout->amount, 'Payout failed after 3 attempts');
        }
    }

    public function failed(\Exception $exception): void
    {
        Log::channel('audit')->error('PayoutProcessingJob failed permanently', [
            'correlation_id' => $this->correlationId,
            'error' => $exception->getMessage(),
        ]);
    }
}

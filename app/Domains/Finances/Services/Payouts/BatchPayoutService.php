<?php

namespace App\Domains\Finances\Services\Payouts;

use App\Domains\Finances\Models\PaymentTransaction;
use Illuminate\Support\{Carbon, Facades, Str};
use Illuminate\Support\Facades\{DB, Log};
use Exception;

/**
 * Сервис для управления пакетными выплатами (batch payouts).
 * 
 * Функциональность:
 * - Создание пакетов выплат сотрудникам/партнёрам
 * - Обработка выплат через Tochka Bank или Sber
 * - Отслеживание статуса каждой выплаты
 * - Возможность отмены незавершённых выплат
 */
class BatchPayoutService
{
    /**
     * Создать пакет выплат.
     * 
     * @param array $payouts Массив выплат: [['user_id' => 1, 'amount' => 1000, 'description' => '...'], ...]
     * @param string $correlationId Идентификатор для отслеживания
     * @return array Информация о созданном пакете
     */
    public function createBatch(array $payouts, string $correlationId = null): array
    {
        $correlationId = $correlationId ?? Str::uuid()->toString();

        DB::beginTransaction();

        try {
            $batchId = Str::uuid()->toString();
            $totalAmount = 0;

            foreach ($payouts as $payout) {
                $amount = (float) $payout['amount'];
                $totalAmount += $amount;

                // Создаём запись транзакции для выплаты
                PaymentTransaction::create([
                    'user_id' => $payout['user_id'],
                    'tenant_id' => auth('tenant')->user()->id ?? null,
                    'amount' => $amount,
                    'status' => PaymentTransaction::STATUS_PENDING,
                    'type' => 'payout',
                    'gateway' => $payout['gateway'] ?? 'tochka',
                    'metadata' => [
                        'batch_id' => $batchId,
                        'description' => $payout['description'] ?? 'Payout',
                        'recipient_bank' => $payout['recipient_bank'] ?? null,
                        'recipient_account' => $payout['recipient_account'] ?? null,
                    ],
                    'correlation_id' => $correlationId,
                ]);
            }

            DB::commit();

            Log::info('Batch payout created', [
                'batch_id' => $batchId,
                'count' => count($payouts),
                'total_amount' => $totalAmount,
                'correlation_id' => $correlationId,
            ]);

            return [
                'batch_id' => $batchId,
                'count' => count($payouts),
                'total_amount' => $totalAmount,
                'status' => 'created',
                'correlation_id' => $correlationId,
            ];
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Failed to create batch payout', [
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ]);
            throw $e;
        }
    }

    /**
     * Обработать все выплаты в пакете.
     */
    public function processBatch(string $batchId): array
    {
        try {
            $payouts = PaymentTransaction::where('metadata->batch_id', $batchId)
                ->where('status', PaymentTransaction::STATUS_PENDING)
                ->get();

            if ($payouts->isEmpty()) {
                throw new Exception("No pending payouts found for batch {$batchId}");
            }

            $results = [
                'batch_id' => $batchId,
                'processed' => 0,
                'failed' => 0,
                'details' => [],
            ];

            foreach ($payouts as $payout) {
                try {
                    $this->processSinglePayout($payout);
                    $results['processed']++;
                    $results['details'][] = [
                        'transaction_id' => $payout->id,
                        'status' => 'processed',
                    ];
                } catch (Exception $e) {
                    $results['failed']++;
                    $results['details'][] = [
                        'transaction_id' => $payout->id,
                        'status' => 'failed',
                        'error' => $e->getMessage(),
                    ];
                    
                    Log::error('Failed to process single payout', [
                        'transaction_id' => $payout->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            Log::info('Batch payout processed', [
                'batch_id' => $batchId,
                'processed' => $results['processed'],
                'failed' => $results['failed'],
            ]);

            return $results;
        } catch (Exception $e) {
            Log::error('Failed to process batch payout', [
                'batch_id' => $batchId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Обработать одну выплату.
     */
    private function processSinglePayout(PaymentTransaction $payout): void
    {
        try {
            $gateway = $payout->metadata['gateway'] ?? 'tochka';

            // Здесь интегрируем с реальным API банка (Tochka, Sber)
            // Для демонстрации просто обновляем статус
            
            $payout->update([
                'status' => PaymentTransaction::STATUS_AUTHORIZED,
                'metadata' => array_merge($payout->metadata, [
                    'processed_at' => Carbon::now()->toIso8601String(),
                    'gateway_reference' => 'ref_' . Str::random(20),
                ]),
            ]);

            // Может быть асинхронное подтверждение через webhook
            Log::info('Single payout sent to gateway', [
                'transaction_id' => $payout->id,
                'gateway' => $gateway,
                'amount' => $payout->amount,
            ]);
        } catch (Exception $e) {
            Log::error('Failed to send payout to gateway', [
                'transaction_id' => $payout->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Получить статус пакета.
     */
    public function getBatchStatus(string $batchId): array
    {
        $payouts = PaymentTransaction::where('metadata->batch_id', $batchId)->get();

        if ($payouts->isEmpty()) {
            throw new Exception("Batch {$batchId} not found");
        }

        $statuses = $payouts->groupBy('status')->map->count();
        $totalAmount = $payouts->sum('amount');

        return [
            'batch_id' => $batchId,
            'total_payouts' => $payouts->count(),
            'total_amount' => $totalAmount,
            'status_breakdown' => $statuses->toArray(),
            'created_at' => $payouts->first()->created_at,
        ];
    }

    /**
     * Отменить пакет (только незавершённые выплаты).
     */
    public function cancelBatch(string $batchId, string $reason = null): array
    {
        try {
            $payouts = PaymentTransaction::where('metadata->batch_id', $batchId)
                ->whereIn('status', [
                    PaymentTransaction::STATUS_PENDING,
                    PaymentTransaction::STATUS_AUTHORIZED,
                ])
                ->get();

            $cancelled = 0;

            foreach ($payouts as $payout) {
                $payout->update([
                    'status' => 'cancelled',
                    'metadata' => array_merge($payout->metadata, [
                        'cancellation_reason' => $reason,
                        'cancelled_at' => Carbon::now()->toIso8601String(),
                    ]),
                ]);
                $cancelled++;
            }

            Log::info('Batch cancelled', [
                'batch_id' => $batchId,
                'cancelled_count' => $cancelled,
                'reason' => $reason,
            ]);

            return [
                'batch_id' => $batchId,
                'cancelled_count' => $cancelled,
                'status' => 'cancelled',
            ];
        } catch (Exception $e) {
            Log::error('Failed to cancel batch', [
                'batch_id' => $batchId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}

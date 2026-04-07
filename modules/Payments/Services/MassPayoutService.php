<?php

declare(strict_types=1);

namespace Modules\Payments\Services;

use App\Models\PaymentTransaction;
use App\Services\FraudControlService;
use Illuminate\Database\Connection;
use Illuminate\Log\LogManager;
use Illuminate\Support\Str;
use Modules\Common\Services\AbstractTechnicalVerticalService;

/**
 * Сервис массовых выплат (BatchPayout).
 *
 * КАНОН 2026:
 * - DB::transaction() для каждой группы выплат
 * - FraudControlService::check() перед каждой выплатой
 * - correlation_id обязателен
 * - Лимиты на количество и сумму (config/payments.php)
 * - Логирование через LogManager (не static Log::)
 */
final class MassPayoutService extends AbstractTechnicalVerticalService
{
    public function __construct(
        private readonly Connection          $db,
        private readonly LogManager         $log,
        private readonly FraudControlService $fraud,
    ) {}

    public function isEnabled(): bool
    {
        return (bool) config('payments.mass_payout.enabled', true);
    }

    // ──────────────────────────────────────────────────────────────────
    //  Public API
    // ──────────────────────────────────────────────────────────────────

    /**
     * Инициировать массовую выплату.
     *
     * @param array  $payouts  Список выплат: [{tenant_id, amount_copeki, user_id, phone, description}]
     * @param array  $options  Опции: {maxPayoutPerDay, maxPayouts, delaySeconds}
     *
     * @return array{batchId: string, totalAmount: int, count: int, status: string}
     *
     * @throws \InvalidArgumentException При превышении лимитов
     * @throws \Throwable
     */
    public function initiateBatchPayout(
        array $payouts,
        array $options = [],
    ): array {
        $correlationId  = $this->getCorrelationId();
        $maxPerDay      = (int) ($options['maxPayoutPerDay'] ?? config('payments.mass_payout.max_per_day', 10_000_000));
        $maxCount       = (int) ($options['maxPayouts']      ?? config('payments.mass_payout.max_count', 100));

        if (count($payouts) > $maxCount) {
            throw new \InvalidArgumentException("Превышен лимит количества выплат: {$maxCount}");
        }

        $totalAmount = (int) array_sum(array_column($payouts, 'amount_copeki'));

        if ($totalAmount > $maxPerDay) {
            throw new \InvalidArgumentException("Превышен дневной лимит суммы выплат: {$maxPerDay} коп.");
        }

        $this->log->channel('audit')->info('mass_payout.initiate.start', [
            'correlation_id' => $correlationId,
            'count'          => count($payouts),
            'total_amount'   => $totalAmount,
        ]);

        try {
            $batchId = 'batch_' . Str::uuid()->toString();

            $this->db->transaction(function () use ($payouts, $batchId, $correlationId): void {
                foreach ($payouts as $payout) {
                    // Fraud check per payout
                    $this->fraud->check([
                        'operation_type'  => 'payout',
                        'amount'          => $payout['amount_copeki'],
                        'tenant_id'       => $payout['tenant_id'],
                        'user_id'         => $payout['user_id'] ?? null,
                        'correlation_id'  => $correlationId,
                    ]);

                    PaymentTransaction::create([
                        'tenant_id'      => $payout['tenant_id'],
                        'user_id'        => $payout['user_id'] ?? null,
                        'amount'         => $payout['amount_copeki'],
                        'status'         => 'pending',
                        'batch_id'       => $batchId,
                        'correlation_id' => $correlationId,
                        'description'    => $payout['description'] ?? 'Массовая выплата',
                        'tags'           => ['batch_payout', 'pending'],
                    ]);
                }
            });

            $this->log->channel('audit')->info('mass_payout.initiate.success', [
                'correlation_id' => $correlationId,
                'batch_id'       => $batchId,
                'count'          => count($payouts),
                'total_amount'   => $totalAmount,
            ]);

            return [
                'batchId'     => $batchId,
                'totalAmount' => $totalAmount,
                'count'       => count($payouts),
                'status'      => 'pending',
            ];
        } catch (\Throwable $e) {
            $this->log->channel('audit')->error('mass_payout.initiate.error', [
                'correlation_id' => $correlationId,
                'error'          => $e->getMessage(),
                'trace'          => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Выполнить одну выплату через gateway.
     *
     * @param int    $paymentId  ID записи PaymentTransaction
     * @param string $gateway    Шлюз: tinkoff | tochka | sber
     *
     * @throws \RuntimeException При ошибке gateway
     * @throws \Throwable
     */
    public function executePayout(int $paymentId, string $gateway = 'tinkoff'): void
    {
        $correlationId = $this->getCorrelationId();

        $payment = PaymentTransaction::findOrFail($paymentId);

        $this->log->channel('audit')->info('mass_payout.execute.start', [
            'correlation_id' => $correlationId,
            'payment_id'     => $paymentId,
            'gateway'        => $gateway,
            'amount'         => $payment->amount,
        ]);

        try {
            /** @var \Modules\Payments\Contracts\PaymentGatewayInterface $gatewayService */
            $gatewayService = app(\Modules\Payments\Contracts\PaymentGatewayInterface::class);

            $result = $gatewayService->createPayout(
                $payment->amount,
                $payment->recipient_account ?? '',
                ['payment_id' => $paymentId, 'correlation_id' => $correlationId]
            );

            if (!($result['success'] ?? false)) {
                throw new \RuntimeException('Payout gateway error: ' . ($result['error'] ?? 'unknown'));
            }

            $this->db->transaction(function () use ($payment, $gateway): void {
                $payment->update([
                    'status'      => 'captured',
                    'gateway'     => $gateway,
                    'captured_at' => now(),
                ]);
            });

            $this->log->channel('audit')->info('mass_payout.execute.success', [
                'correlation_id' => $correlationId,
                'payment_id'     => $paymentId,
            ]);
        } catch (\Throwable $e) {
            $this->log->channel('audit')->error('mass_payout.execute.error', [
                'correlation_id' => $correlationId,
                'payment_id'     => $paymentId,
                'error'          => $e->getMessage(),
                'trace'          => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }
}

         *
         * @param array $payouts Массив выплат: [{tenant_id, amount_copeki, user_id, phone, description}]
         * @param string $correlationId Идентификатор корреляции
         * @param array $options Опции: {maxPayoutPerDay, maxPayouts, delaySeconds}
         * @return array{batchId: string, totalAmount: int, count: int, status: string}
         * @throws Exception
         */
        public function initiateBatchPayout(
            array $payouts,
            string $correlationId = '',
            array $options = [],
        ): array {
            try {
                $maxPayoutPerDay = $options['maxPayoutPerDay'] ?? 10000000; // 100 000 ₽
                $maxPayouts = $options['maxPayouts'] ?? 100;
                $delaySeconds = $options['delaySeconds'] ?? 5; // Задержка между платежами
    
                // Валидация
                if (count($payouts) > $maxPayouts) {
                    throw new Exception("Превышен лимит платежей: {$maxPayouts}");
                }
    
                $totalAmount = array_sum(array_column($payouts, 'amount_copeki'));
                if ($totalAmount > $maxPayoutPerDay) {
                    throw new Exception("Превышен дневной лимит: {$maxPayoutPerDay}");
                }
    
                Log::channel('audit')->info('Инициирование массовой выплаты', [
                    'batch_id' => '',
                    'count' => count($payouts),
                    'total_amount' => $totalAmount,
                    'correlation_id' => $correlationId,
                ]);
    
                // Создаём записи платежей
                $batchId = 'batch_' . uniqid();
    
                DB::transaction(function () use ($payouts, $batchId, $correlationId) {
                    foreach ($payouts as $payout) {
                        // Fraud check
                        $this->fraudControlService->checkPayout(
                            $payout['tenant_id'],
                            $payout['amount_copeki'],
                            $correlationId,
                        );
    
                        PaymentTransaction::create([
                            'tenant_id' => $payout['tenant_id'],
                            'user_id' => $payout['user_id'] ?? null,
                            'amount' => $payout['amount_copeki'],
                            'status' => 'pending',
                            'batch_id' => $batchId,
                            'correlation_id' => $correlationId,
                            'description' => $payout['description'] ?? 'Массовая выплата',
                        ]);
                    }
                });
    
                return [
                    'batchId' => $batchId,
                    'totalAmount' => $totalAmount,
                    'count' => count($payouts),
                    'status' => 'pending',
                ];
            } catch (Exception $e) {
                Log::channel('audit')->error('Ошибка при инициировании массовой выплаты', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'correlation_id' => $correlationId,
                ]);
    
                throw $e;
            }
        }
    
        /**
         * Выполняет выплату через gateway.
         *
         * @param int $paymentId ID платежа
         * @param string $gateway Шлюз: tinkoff, tochka, sber
         * @param string $correlationId Идентификатор корреляции
         * @return bool
         * @throws Exception
         */
        public function executePayout(
            int $paymentId,
            string $gateway = 'tinkoff',
            string $correlationId = '',
        ): bool {
            try {
                $payment = PaymentTransaction::findOrFail($paymentId);
    
                Log::channel('audit')->info('Выполнение выплаты', [
                    'payment_id' => $paymentId,
                    'gateway' => $gateway,
                    'amount' => $payment->amount,
                    'correlation_id' => $correlationId,
                ]);
    
                // Интеграция с gateway через PaymentGatewayInterface
                $gatewayService = app(\Modules\Payments\Contracts\PaymentGatewayInterface::class);
                $result = $gatewayService->createPayout(
                    $payment->amount,
                    $payment->recipient_account ?? '',
                    ['payment_id' => $paymentId, 'correlation_id' => $correlationId]
                );
    
                if (!$result['success']) {
                    throw new \Exception('Payout failed: ' . ($result['error'] ?? 'Unknown error'));
                }
    
                DB::transaction(function () use ($payment, $gateway, $result) {
                    $payment->update([
                        'status' => 'captured',
                        'gateway' => $gateway,
                        'captured_at' => now(),
                    ]);
                });
    
                Log::channel('audit')->info('Выплата выполнена', [
                    'payment_id' => $paymentId,
                    'correlation_id' => $correlationId,
                ]);
    
                return true;
            } catch (Exception $e) {
                Log::channel('audit')->error('Ошибка при выполнении выплаты', [
                    'payment_id' => $paymentId,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'correlation_id' => $correlationId,
                ]);
    
                throw $e;
            }
        }
}

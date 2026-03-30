<?php declare(strict_types=1);

namespace Modules\Payments\Services;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class MassPayoutService extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function __construct(
            private Connection $connection,
            private FraudControlService $fraudControlService,
        ) {}
    
        /**
         * Инициирует массовую выплату с лимитами и ML-фрод-скорингом.
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

<?php declare(strict_types=1);

namespace App\Domains\Medical\Services;



use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
final readonly class TestOrderService
{

    public function __construct(private readonly FraudControlService $fraud,
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger, private readonly Guard $guard) {}

        public function createTestOrder(
            int $tenantId,
            int $appointmentId,
            int $patientId,
            int $clinicId,
            array $tests,
            float $totalAmount,
            ?string $correlationId = null
    ): MedicalTestOrder {
            $correlationId ??= Str::uuid()->toString();

            try {
                $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'mutation', amount: 0, correlationId: $correlationId ?? '');
    $this->db->transaction(function () use (
                    $tenantId,
                    $appointmentId,
                    $patientId,
                    $clinicId,
                    $tests,
                    $totalAmount,
                    $correlationId
    ) {
                    $testOrder = MedicalTestOrder::create([
                        'tenant_id' => $tenantId,
                        'appointment_id' => $appointmentId,
                        'patient_id' => $patientId,
                        'clinic_id' => $clinicId,
                        'test_order_number' => Str::uuid()->toString(),
                        'tests' => $tests,
                        'total_amount' => $totalAmount,
                        'commission_amount' => $totalAmount * 0.14,
                        'status' => 'ordered',
                        'payment_status' => 'unpaid',
                        'ordered_at' => now(),
                        'correlation_id' => $correlationId,
                    ]);

                    TestOrderCreated::dispatch($testOrder, $correlationId);

                    $this->logger->info('Medical test order created', [
                        'test_order_id' => $testOrder->id,
                        'patient_id' => $patientId,
                        'clinic_id' => $clinicId,
                        'total_amount' => $totalAmount,
                        'commission_amount' => $testOrder->commission_amount,
                        'correlation_id' => $correlationId,
                    ]);

                    return $testOrder;
                });
            } catch (Throwable $e) {
                $this->logger->error('Failed to create test order', [
                    'error' => $e->getMessage(),
                    'correlation_id' => $correlationId,
                ]);
                throw $e;
            }
        }

        public function completeTestOrder(
            MedicalTestOrder $testOrder,
            array $results,
            ?string $correlationId = null
    ): MedicalTestOrder {
            $correlationId ??= Str::uuid()->toString();

            try {
                $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'mutation', amount: 0, correlationId: $correlationId ?? '');
    $this->db->transaction(function () use ($testOrder, $results, $correlationId) {
                    $testOrder->update([
                        'status' => 'completed',
                        'completed_at' => now(),
                        'results' => $results,
                        'correlation_id' => $correlationId,
                    ]);

                    $this->logger->info('Medical test order completed', [
                        'test_order_id' => $testOrder->id,
                        'patient_id' => $testOrder->patient_id,
                        'correlation_id' => $correlationId,
                    ]);

                    return $testOrder;
                });
            } catch (Throwable $e) {
                $this->logger->error('Failed to complete test order', [
                    'test_order_id' => $testOrder->id,
                    'error' => $e->getMessage(),
                    'correlation_id' => $correlationId,
                ]);
                throw $e;
            }
        }
}

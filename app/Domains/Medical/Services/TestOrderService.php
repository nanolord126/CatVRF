<?php declare(strict_types=1);

namespace App\Domains\Medical\Services;

use App\Services\Security\FraudControlService;
use Illuminate\Support\Facades\Log;

use App\Domains\Medical\Events\TestOrderCreated;
use App\Domains\Medical\Models\MedicalTestOrder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

final class TestOrderService
{
    public function createTestOrder(
        int $tenantId,
        int $appointmentId,
        int $patientId,
        int $clinicId,
        array $tests,
        float $totalAmount,
        ?string $correlationId = null,
    ): MedicalTestOrder {
        // Canon 2026: Mandatory Fraud Check & Audit
        
        \App\Services\Security\FraudControlService::check(['method' => 'createTestOrder'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL createTestOrder', ['domain' => __CLASS__]);

        $correlationId ??= Str::uuid()->toString();

        try {
            return DB::transaction(function () use (
                $tenantId,
                $appointmentId,
                $patientId,
                $clinicId,
                $tests,
                $totalAmount,
                $correlationId,
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

                Log::channel('audit')->info('Medical test order created', [
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
            Log::channel('audit')->error('Failed to create test order', [
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ]);
            throw $e;
        }
    }

    public function completeTestOrder(
        MedicalTestOrder $testOrder,
        array $results,
        ?string $correlationId = null,
    ): MedicalTestOrder {
        // Canon 2026: Mandatory Fraud Check & Audit
        
        \App\Services\Security\FraudControlService::check(['method' => 'completeTestOrder'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL completeTestOrder', ['domain' => __CLASS__]);

        $correlationId ??= Str::uuid()->toString();

        try {
            return DB::transaction(function () use ($testOrder, $results, $correlationId) {
                $testOrder->update([
                    'status' => 'completed',
                    'completed_at' => now(),
                    'results' => $results,
                    'correlation_id' => $correlationId,
                ]);

                Log::channel('audit')->info('Medical test order completed', [
                    'test_order_id' => $testOrder->id,
                    'patient_id' => $testOrder->patient_id,
                    'correlation_id' => $correlationId,
                ]);

                return $testOrder;
            });
        } catch (Throwable $e) {
            Log::channel('audit')->error('Failed to complete test order', [
                'test_order_id' => $testOrder->id,
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ]);
            throw $e;
        }
    }
}

<?php declare(strict_types=1);

namespace App\Domains\Pharmacy\Services;

use App\Domains\Pharmacy\Models\Medicine;
use App\Domains\Pharmacy\Models\Prescription;
use App\Domains\Pharmacy\Models\PharmacyOrder;
use App\Domains\Pharmacy\Events\PharmacyOrderCreated;
use App\Domains\Pharmacy\Events\PrescriptionVerified;
use App\Services\FraudControlService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;

final class PharmacyService
{
    public function __construct(
        private readonly FraudControlService $fraudControlService,
    ) {}

    public function createOrder(array $medicines, int $prescriptionId, Carbon $deliveryDate, int $clientId, int $tenantId, string $correlationId): PharmacyOrder
    {
        // Canon 2026: Mandatory Fraud Check & Audit
        
        \App\Services\Security\FraudControlService::check(['method' => 'createOrder'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL createOrder', ['domain' => __CLASS__]);

        return DB::transaction(function () use ($medicines, $prescriptionId, $deliveryDate, $clientId, $tenantId, $correlationId) {
            $this->fraudControlService->check(
                userId: $clientId,
                operationType: 'pharmacy_order',
                amount: 0,
                correlationId: $correlationId,
            );

            $prescription = null;
            if ($prescriptionId) {
                $prescription = Prescription::findOrFail($prescriptionId);
                if (!$prescription->isVerified()) {
                    throw new \Exception("Prescription {$prescriptionId} is not verified");
                }
            }

            $totalPrice = 0;
            $medicinesJson = [];

            foreach ($medicines as $medicineId => $quantity) {
                $medicine = Medicine::lockForUpdate()->findOrFail($medicineId);

                if ($medicine->current_stock < $quantity) {
                    throw new \Exception("Insufficient stock for medicine {$medicineId}");
                }

                $medicineTotalPrice = $medicine->price * $quantity;
                $totalPrice += $medicineTotalPrice;

                $medicinesJson[] = [
                    'medicine_id' => $medicineId,
                    'name' => $medicine->name,
                    'quantity' => $quantity,
                    'price' => $medicine->price,
                    'total' => $medicineTotalPrice,
                ];

                $medicine->decrement('current_stock', $quantity);
            }

            $order = PharmacyOrder::create([
                'tenant_id' => $tenantId,
                'uuid' => Str::uuid(),
                'correlation_id' => $correlationId,
                'prescription_id' => $prescriptionId,
                'client_id' => $clientId,
                'medicines' => $medicinesJson,
                'total_price' => $totalPrice,
                'delivery_date' => $deliveryDate,
                'status' => 'pending',
                'idempotency_key' => md5("{$clientId}:{$prescriptionId}:{$deliveryDate}:{$tenantId}"),
            ]);

            PharmacyOrderCreated::dispatch($order->id, $tenantId, $clientId, $totalPrice, $correlationId);
            Log::channel('audit')->info('Pharmacy order created', [
                'order_id' => $order->id,
                'medicines_count' => count($medicines),
                'correlation_id' => $correlationId,
            ]);

            return $order;
        });
    }

    public function verifyPrescription(int $prescriptionId, int $verifiedBy, int $tenantId, string $correlationId): Prescription
    {
        // Canon 2026: Mandatory Fraud Check & Audit
        
        \App\Services\Security\FraudControlService::check(['method' => 'verifyPrescription'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL verifyPrescription', ['domain' => __CLASS__]);

        return DB::transaction(function () use ($prescriptionId, $verifiedBy, $tenantId, $correlationId) {
            $prescription = Prescription::lockForUpdate()
                ->where('id', $prescriptionId)
                ->where('tenant_id', $tenantId)
                ->firstOrFail();

            if ($prescription->isVerified()) {
                throw new \Exception("Prescription {$prescriptionId} is already verified");
            }

            $prescription->update([
                'status' => 'verified',
                'verified_at' => now(),
                'verified_by' => $verifiedBy,
            ]);

            PrescriptionVerified::dispatch($prescription->id, $tenantId, $verifiedBy, $correlationId);
            Log::channel('audit')->info('Prescription verified', [
                'prescription_id' => $prescription->id,
                'verified_by' => $verifiedBy,
                'correlation_id' => $correlationId,
            ]);

            return $prescription;
        });
    }

    public function markDelivered(int $orderId, int $tenantId, string $correlationId): PharmacyOrder
    {
        // Canon 2026: Mandatory Fraud Check & Audit
        
        \App\Services\Security\FraudControlService::check(['method' => 'markDelivered'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL markDelivered', ['domain' => __CLASS__]);

        $order = PharmacyOrder::lockForUpdate()
            ->where('id', $orderId)
            ->where('tenant_id', $tenantId)
            ->firstOrFail();

        if (!$order->isPending()) {
            throw new \Exception("Order {$orderId} is not in pending state");
        }

        $order->update(['status' => 'delivered']);

        Log::channel('audit')->info('Pharmacy order delivered', [
            'order_id' => $order->id,
            'correlation_id' => $correlationId,
        ]);

        return $order;
    }
}

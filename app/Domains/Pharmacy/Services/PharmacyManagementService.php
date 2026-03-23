<?php declare(strict_types=1);

namespace App\Domains\Pharmacy\Services;

use App\Domains\Pharmacy\Models\Medication;
use App\Domains\Pharmacy\Models\PharmacyOrder;
use App\Domains\Pharmacy\Models\PharmacyOrderItem;
use App\Domains\Pharmacy\Models\Prescription;
use App\Services\FraudControlService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

final class PharmacyManagementService
{
    public function __construct(
        private readonly string $correlationId = ''
    ) {
        if (empty($this->correlationId)) {
            $this->correlationId = (string) Str::uuid();
        }
    }

    /**
     * Создание заказа в аптеке с проверкой рецептов и фрод-контролем.
     */
    public function createOrder(int $userId, int $pharmacyId, array $items): PharmacyOrder
    {
        Log::channel('audit')->info('Pharmacy order initiation', [
            'user_id' => $userId,
            'pharmacy_id' => $pharmacyId,
            'correlation_id' => $this->correlationId
        ]);

        return DB::transaction(function () use ($userId, $pharmacyId, $items) {
            $totalAmount = 0;
            $itemsToCreate = [];

            foreach ($items as $item) {
                $medication = Medication::lockForUpdate()->findOrFail($item['medication_id']);

                // 1. Проверка наличия
                if ($medication->stock_quantity < $item['quantity']) {
                    throw new RuntimeException("Insufficient stock for medication: {$medication->name}");
                }

                // 2. Проверка рецепта
                if ($medication->requires_prescription) {
                    $this->validatePrescription($userId, $medication->id);
                }

                $totalAmount += $medication->price * $item['quantity'];
                
                $itemsToCreate[] = [
                    'medication_id' => $medication->id,
                    'quantity' => $item['quantity'],
                    'price_at_order' => $medication->price,
                    'correlation_id' => $this->correlationId
                ];

                // 3. Списание остатков
                $medication->decrement('stock_quantity', $item['quantity']);
            }

            // 4. Fraud Check
            FraudControlService::check([
                'type' => 'pharmacy_order',
                'user_id' => $userId,
                'amount' => $totalAmount,
                'correlation_id' => $this->correlationId
            ]);

            // 5. Создание заказа
            $order = PharmacyOrder::create([
                'user_id' => $userId,
                'pharmacy_id' => $pharmacyId,
                'total_amount' => $totalAmount,
                'status' => 'pending',
                'idempotency_key' => (string) Str::uuid(),
                'correlation_id' => $this->correlationId
            ]);

            $order->items()->createMany($itemsToCreate);

            return $order;
        });
    }

    /**
     * Валидация наличия активного рецепта.
     */
    private function validatePrescription(int $userId, int $medicationId): void
    {
        $hasActivePrescription = Prescription::where('user_id', $userId)
            ->where('status', 'verified')
            ->where('expires_at', '>=', now())
            ->exists();

        if (!$hasActivePrescription) {
            throw new RuntimeException("Valid prescription required for medication ID: {$medicationId}");
        }
    }

    /**
     * Поиск лекарств по МНН или названию.
     */
    public function searchMedications(string $query): Collection
    {
        return Medication::where('name', 'like', "%{$query}%")
            ->orWhere('inn', 'like', "%{$query}%")
            ->where('stock_quantity', '>', 0)
            ->get();
    }
}
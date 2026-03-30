<?php declare(strict_types=1);

namespace App\Domains\Logistics\Http\Controllers;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ShipmentInsuranceController extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function getInsurance(int $shipmentId): JsonResponse
        {
            try {
                $insurance = ShipmentInsurance::where('shipment_id', $shipmentId)->first();
                return response()->json(['success' => true, 'data' => $insurance, 'correlation_id' => Str::uuid()]);
            } catch (\Throwable $e) {
                return response()->json(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()], 500);
            }
        }

        public function addInsurance(int $shipmentId): JsonResponse
        {
            try {
                $correlationId = Str::uuid()->toString();

                DB::transaction(function () use ($shipmentId, $correlationId) {
                    $shipment = \App\Domains\Logistics\Models\Shipment::findOrFail($shipmentId);

                    ShipmentInsurance::create([
                        'tenant_id' => $shipment->tenant_id,
                        'shipment_id' => $shipmentId,
                        'insurance_amount' => request('insurance_amount'),
                        'premium' => request('premium'),
                        'status' => 'active',
                        'correlation_id' => $correlationId,
                    ]);

                    Log::channel('audit')->info('Shipment insurance added', [
                        'shipment_id' => $shipmentId,
                        'insurance_amount' => request('insurance_amount'),
                        'correlation_id' => $correlationId,
                    ]);
                });

                return response()->json(['success' => true, 'data' => null, 'correlation_id' => $correlationId], 201);
            } catch (\Throwable $e) {
                return response()->json(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()], 400);
            }
        }
}

<?php declare(strict_types=1);

namespace App\Domains\Logistics\Http\Controllers;


use Psr\Log\LoggerInterface;
use App\Http\Controllers\Controller;

final class ShipmentInsuranceController extends Controller
{
    public function __construct(
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger) {}


    public function getInsurance(int $shipmentId): JsonResponse
        {
            try {
                $insurance = ShipmentInsurance::where('shipment_id', $shipmentId)->first();
                return new \Illuminate\Http\JsonResponse(['success' => true, 'data' => $insurance, 'correlation_id' => Str::uuid()]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()], 500);
            }
        }

        public function addInsurance(int $shipmentId): JsonResponse
        {
            try {
                $correlationId = Str::uuid()->toString();

                $this->db->transaction(function () use ($shipmentId, $correlationId) {
                    $shipment = \App\Domains\Logistics\Models\Shipment::findOrFail($shipmentId);

                    ShipmentInsurance::create([
                        'tenant_id' => $shipment->tenant_id,
                        'shipment_id' => $shipmentId,
                        'insurance_amount' => $request->input('insurance_amount'),
                        'premium' => $request->input('premium'),
                        'status' => 'active',
                        'correlation_id' => $correlationId,
                    ]);

                    $this->logger->info('Shipment insurance added', [
                        'shipment_id' => $shipmentId,
                        'insurance_amount' => $request->input('insurance_amount'),
                        'correlation_id' => $correlationId,
                    ]);
                });

                return new \Illuminate\Http\JsonResponse(['success' => true, 'data' => null, 'correlation_id' => $correlationId], 201);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()], 400);
            }
        }

    /**
     * Get the string representation of this instance.
     *
     * @return string The string representation
     */
    public function __toString(): string
    {
        return static::class;
    }

    /**
     * Get debug information for this instance.
     *
     * @return array<string, mixed> Debug data including class name and state
     */
    public function toDebugArray(): array
    {
        return [
            'class' => static::class,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}

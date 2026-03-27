<?php

declare(strict_types=1);

namespace App\Domains\Electronics\Services;

use App\Domains\Electronics\DTOs\WarrantyRegisterDto;
use App\Domains\Electronics\Models\ElectronicsProduct;
use App\Domains\Electronics\Models\ElectronicsWarranty;
use App\Services\FraudControlService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * ElectronicsWarrantyService - Manages high-value gadget post-sale assurance.
 * Requirement: Final class, strict types, DB transactions, audit logging.
 */
final readonly class ElectronicsWarrantyService
{
    public function __construct(
        private readonly FraudControlService $fraud,
    ) {}

    /**
     * Register a new hardware warranty after a successful sale.
     * Layer: Domain Service (3/9)
     */
    public function registerWarranty(WarrantyRegisterDto $dto): ElectronicsWarranty
    {
        $correlationId = $dto->correlationId ?: (string) Str::uuid();

        Log::channel('audit')->info('LAYER-3: Registering electronics warranty', [
            'serial' => $dto->serialNumber,
            'order_id' => $dto->orderId,
            'correlation_id' => $correlationId,
        ]);

        // 1. Double registration check
        $exists = ElectronicsWarranty::where('serial_number', $dto->serialNumber)->exists();
        if ($exists) {
            Log::channel('audit')->error('LAYER-3: Serial number already registered', [
                'serial' => $dto->serialNumber,
                'correlation_id' => $correlationId,
            ]);
            throw new \RuntimeException("Warranty already exists for serial: {$dto->serialNumber}");
        }

        // 2. Fraud Check for suspicious registration patterns
        $this->fraud->check('electronics_warranty_reg', [
            'user_id' => $dto->userId,
            'serial' => $dto->serialNumber,
        ]);

        return DB::transaction(function () use ($dto, $correlationId) {
            $product = ElectronicsProduct::findOrFail($dto->productId);

            $warranty = ElectronicsWarranty::create([
                'product_id' => $product->id,
                'order_id' => $dto->orderId,
                'user_id' => $dto->userId,
                'serial_number' => $dto->serialNumber,
                'starts_at' => now(),
                'expires_at' => now()->addMonths($dto->monthsDuration),
                'status' => 'active',
                'terms' => 'Standard manufacturer warranty for ' . $product->brand,
                'correlation_id' => $correlationId,
            ]);

            Log::channel('audit')->info('LAYER-3: Warranty registered successfully', [
                'id' => $warranty->id,
                'expires' => $warranty->expires_at->toDateString(),
                'correlation_id' => $correlationId,
            ]);

            return $warranty;
        });
    }

    /**
     * Terminate or void a warranty (e.g. tampering detected).
     */
    public function voidWarranty(string $serialNumber, string $reason, string $correlationId = ''): bool
    {
        $correlationId = $correlationId ?: (string) Str::uuid();

        return DB::transaction(function () use ($serialNumber, $reason, $correlationId) {
            $warranty = ElectronicsWarranty::where('serial_number', $serialNumber)->firstOrFail();
            
            $warranty->update([
                'status' => 'void',
                'terms' => $warranty->terms . "\nVOID REASON: " . $reason,
                'correlation_id' => $correlationId,
            ]);

            Log::channel('audit')->warning('LAYER-3: Warranty voided', [
                'serial' => $serialNumber,
                'reason' => $reason,
                'correlation_id' => $correlationId,
            ]);

            return true;
        });
    }

    /**
     * Check if a device is currently under valid warranty.
     */
    public function checkStatus(string $serialNumber): array
    {
        $warranty = ElectronicsWarranty::where('serial_number', $serialNumber)->first();

        if (!$warranty) {
            return ['status' => 'not_found', 'is_valid' => false];
        }

        $isValid = $warranty->status === 'active' && $warranty->expires_at->isFuture();

        return [
            'status' => $warranty->status,
            'is_valid' => $isValid,
            'expires_at' => $warranty->expires_at->toDateString(),
            'brand' => $warranty->product->brand,
            'model' => $warranty->product->name,
        ];
    }
}

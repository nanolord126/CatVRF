<?php

declare(strict_types=1);


namespace App\Domains\Pharmacy\Services;

use App\Domains\Pharmacy\Models\PharmacyConsumable;
use App\Services\FraudControlService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final /**
 * ConsumableDeductionService
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class ConsumableDeductionService
{
    public function __construct(private readonly FraudControlService $fraud) {}

    public function deduct(int $id, int $qty, string $correlationId): void
    {
        $this->fraud->check(['id' => $id, 'qty' => $qty]);
        DB::transaction(function () use ($id, $qty, $correlationId) {
            $c = PharmacyConsumable::findOrFail($id);
            $c->decrement('stock', $qty);
            Log::channel('audit')->info("Consumable deducted", ['id' => $id, 'qty' => $qty, 'correlation_id' => $correlationId]);
        });
    }
}

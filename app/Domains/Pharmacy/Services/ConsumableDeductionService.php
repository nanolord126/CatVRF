<?php declare(strict_types=1);

namespace App\Domains\Pharmacy\Services;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ConsumableDeductionService extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
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

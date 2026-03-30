<?php declare(strict_types=1);

namespace Modules\Finances\Http\Resources;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class PaymentTransactionResource extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function toArray(Request $request): array
        {
            return [
                'id' => $this->id,
                'user_id' => $this->user_id,
                'amount' => (float) $this->amount,
                'description' => $this->description,
                'reference_id' => $this->reference_id,
                'status' => $this->status,
                'payment_method' => $this->payment_method,
                'created_at' => $this->created_at?->toIso8601String(),
                'updated_at' => $this->updated_at?->toIso8601String(),
            ];
        }
}

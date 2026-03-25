declare(strict_types=1);

<?php

declare(strict_types=1);

namespace App\Domains\Finances\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final /**
 * PaymentTransactionResource
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class PaymentTransactionResource extends JsonResource
{
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

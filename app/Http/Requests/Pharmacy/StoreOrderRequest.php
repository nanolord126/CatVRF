<?php declare(strict_types=1);

namespace App\Http\Requests\Pharmacy;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class StoreOrderRequest extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function authorize(): bool
        {
            // CANON 2026: Fraud Check in FormRequest
            if (auth()->check()) {
                $correlationId = $this->header('X-Correlation-ID') ?? \Illuminate\Support\Str::uuid()->toString();
                $fraudResult = app(\App\Services\FraudControlService::class)->check(
                    (int) auth()->id(),
                    'form_request',
                    (int) ($this->input('amount', 0)),
                    $this->ip(),
                    $this->header('X-Device-Fingerprint'),
                    $correlationId,
                );
                if ($fraudResult['decision'] === 'block') {
                    \Illuminate\Support\Facades\Log::channel('fraud_alert')->warning('FormRequest blocked', [
                        'class'          => __CLASS__,
                        'correlation_id' => $correlationId,
                        'score'          => $fraudResult['score'],
                    ]);
                    return false;
                }
            }
            return auth()->check();
        }

        public function rules(): array
        {
            return [
                'prescription_id' => ['sometimes', 'integer', 'exists:prescriptions,id'],
                'client_id' => ['required', 'integer', 'exists:users,id'],
                'medicines' => ['required', 'json'],
                'delivery_date' => ['required', 'date', 'after:today'],
            ];
        }
}

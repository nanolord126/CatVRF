<?php declare(strict_types=1);

namespace App\Http\Requests\OfficeCatering;

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
                'client_id' => ['required', 'integer', 'exists:corporate_clients,id'],
                'menu_id' => ['required', 'integer', 'exists:office_menus,id'],
                'portions' => ['required', 'integer', 'min:1', 'max:500'],
                'delivery_date' => ['required', 'date', 'after:today'],
                'delivery_time' => ['required', 'date_format:H:i', 'between:08:00,17:00'],
            ];
        }
}

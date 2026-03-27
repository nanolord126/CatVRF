<?php

declare(strict_types=1);


namespace App\Http\Requests\Furniture;

use Illuminate\Foundation\Http\FormRequest;

final /**
 * StoreOrderRequest
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class StoreOrderRequest extends FormRequest
{
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
            'item_id' => ['required', 'integer', 'exists:furniture_items,id'],
            'client_id' => ['required', 'integer', 'exists:users,id'],
            'client_address' => ['required', 'string', 'max:500'],
            'delivery_date' => ['required', 'date', 'after:today'],
            'needs_assembly' => ['sometimes', 'boolean'],
            'assembly_date' => ['sometimes', 'date', 'after:delivery_date'],
        ];
    }
}

<?php
declare(strict_types=1);

namespace App\Http\Requests\FarmDirect;

use Illuminate\Foundation\Http\FormRequest;

final class StoreOrderRequest extends FormRequest
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
        return auth()->check(); // Tenant scoping handled in service
    }

    public function rules(): array
    {
        return [
            'product_id' => ['required', 'integer', 'exists:farm_products,id'],
            'quantity_kg' => ['required', 'numeric', 'min:0.5', 'max:500'],
            'delivery_date' => ['required', 'date', 'after:today'],
            'delivery_address' => ['required', 'string', 'max:500'],
            'phone' => ['required', 'string', 'regex:/^\\+?[0-9]{10,15}$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'quantity_kg.min' => 'Минимальный вес - 0,5 кг',
            'delivery_date.after' => 'Дата доставки должна быть в будущем',
            'phone.regex' => 'Некорректный номер телефона',
        ];
    }
}

<?php
declare(strict_types=1);

namespace App\Http\Requests;

final class PaymentInitRequest extends BaseApiRequest
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
                \Illuminate\Support\Facades\$this->log->channel('fraud_alert')->warning('FormRequest blocked', [
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
            'amount' => ['required', 'integer', 'min:100', 'max:50000000'],  // 100 коп - 500 000 ₽
            'currency' => ['required', 'string', 'in:RUB,USD,EUR'],
            'description' => ['required', 'string', 'min:3', 'max:255'],
            'customer_email' => ['required', 'email', 'max:255'],
            'return_url' => ['required', 'url', 'max:2048'],
            'metadata' => ['nullable', 'json'],
            'hold' => ['nullable', 'boolean'],  // Холд вместо списания
            'idempotency_key' => ['nullable', 'string', 'max:255'],
        ];
    }
    
    public function messages(): array
    {
        return [
            'amount.required' => 'Amount is required',
            'amount.integer' => 'Amount must be an integer (in kopecks)',
            'amount.min' => 'Amount must be at least 100 kopecks',
            'amount.max' => 'Amount must not exceed 500,000 RUB',
            'currency.required' => 'Currency is required',
            'currency.in' => 'Currency must be one of: RUB, USD, EUR',
            'description.required' => 'Description is required',
            'customer_email.required' => 'Customer email is required',
            'customer_email.email' => 'Customer email must be valid',
            'return_url.required' => 'Return URL is required',
            'return_url.url' => 'Return URL must be a valid URL',
        ];
    }
}

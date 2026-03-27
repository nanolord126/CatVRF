<?php
declare(strict_types=1);

namespace App\Http\Requests;

final class CreateApiKeyRequest extends BaseApiRequest
{
    /**
     * Авторизация запроса.
     */
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
        return auth()->check() && (auth()->user()->isBusinessOwner() || auth()->user()->hasAbility('manage_api_keys'));
    }

    /**
     * Валидационные правила.
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'abilities' => 'array|nullable',
            'abilities.*' => 'string|in:read,write,delete,manage_payments,manage_webhooks',
            'expires_in_days' => 'integer|min:1|max:730|nullable',
        ];
    }

    /**
     * Пользовательские сообщения об ошибках.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Название API ключа обязательно',
            'name.max' => 'Название не может быть длиннее 255 символов',
            'expires_in_days.max' => 'API ключ не может быть действителен более 730 дней',
        ];
    }
}

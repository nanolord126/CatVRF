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
        if (class_exists(\App\Services\Fraud\FraudControlService::class) && auth()->check()) {
            $fraudScore = app(\App\Services\Fraud\FraudControlService::class)->scoreOperation(new \stdClass());
            if ($fraudScore > 0.7 && !auth()->user()->hasRole('admin')) {
                \Illuminate\Support\Facades\Log::channel('audit')->warning('Fraud check blocked request', ['class' => __CLASS__, 'score' => $fraudScore]);
                return false;
            }
        }
        return auth()->user()->isBusinessOwner() || auth()->user()->hasAbility('manage_api_keys');
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

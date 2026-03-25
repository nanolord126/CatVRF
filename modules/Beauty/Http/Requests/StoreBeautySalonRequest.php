declare(strict_types=1);

<?php declare(strict_types=1);

namespace App\Modules\Beauty\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Валидация создания салона красоты.
 * Production 2026.
 */
final class StoreBeautySalonRequest extends FormRequest
{
    public function authorize(): bool
    {
        // CANON 2026: Fraud Check in FormRequest
        if (class_exists(\App\Services\Fraud\FraudControlService::class) && auth()->check()) {
            $fraudScore = app(\App\Services\Fraud\FraudControlService::class)->scoreOperation(new \stdClass());
            if ($fraudScore > 0.7 && !auth()->user()->hasRole('admin')) {
                \Illuminate\Support\Facades\$this->log->channel('audit')->warning('Fraud check blocked request', ['class' => __CLASS__, 'score' => $fraudScore]);
                return false;
            }
        }
        return auth()->check() && auth()->user()->tenant_id === tenant('id');
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string', 'max:500'],
            'phone' => ['required', 'string', 'regex:/^\+?[0-9\s\-()]{10,}$/'],
            'email' => ['required', 'email', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'working_hours' => ['nullable', 'array'],
            'working_hours.*' => ['array'],
            'metadata' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Название салона обязательно',
            'address.required' => 'Адрес обязателен',
            'phone.required' => 'Телефон обязателен',
            'phone.regex' => 'Некорректный формат телефона',
            'email.required' => 'Email обязателен',
            'email.email' => 'Email должен быть корректным',
        ];
    }
}

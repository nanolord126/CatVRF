declare(strict_types=1);

<?php declare(strict_types=1);

namespace App\Modules\Beauty\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Валидация создания записи на услугу.
 * Production 2026.
 */
final class StoreAppointmentRequest extends FormRequest
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
            'salon_id' => ['required', 'integer', 'exists:beauty_salons,id'],
            'service_id' => ['required', 'integer', 'exists:beauty_services,id'],
            'master_id' => ['required', 'integer', 'exists:beauty_masters,id'],
            'datetime' => ['required', 'date_format:Y-m-d H:i', 'after:now'],
            'notes' => ['nullable', 'string', 'max:500'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'metadata' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'salon_id.required' => 'Выберите салон',
            'service_id.required' => 'Выберите услугу',
            'master_id.required' => 'Выберите мастера',
            'datetime.required' => 'Выберите дату и время',
            'datetime.after' => 'Дата должна быть в будущем',
        ];
    }
}

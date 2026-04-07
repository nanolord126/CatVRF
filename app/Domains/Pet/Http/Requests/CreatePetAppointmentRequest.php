<?php declare(strict_types=1);

namespace App\Domains\Pet\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

/**
 * Валидация запроса на создание PetAppointment.
 * Канон CatVRF 2026: declare(strict_types=1), final class, correlation_id.
 */
final class CreatePetAppointmentRequest extends FormRequest
{
    /**
     * Определяет, авторизован ли пользователь для запроса.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Правила валидации.
     */
    public function rules(): array
    {
        return [
            'pet_id' => 'required|integer|min:1',
            'clinic_id' => 'required|integer|min:1',
            'service_id' => 'required|integer|min:1',
            'owner_id' => 'required|integer|min:1',
            'starts_at' => 'date',
            'total_price' => 'required|numeric|min:0',
        ];
    }

    /**
     * Получить correlation_id из заголовка или сгенерировать новый.
     */
    public function correlationId(): string
    {
        return $this->header('X-Correlation-ID', (string) Str::uuid());
    }

    /**
     * Определить, является ли запрос B2B.
     */
    public function isB2B(): bool
    {
        return $this->has('inn') && $this->has('business_card_id');
    }

    /**
     * Атрибуты с человеко-читаемыми именами.
     */
    public function attributes(): array
    {
        return [
            'correlation_id' => 'ID корреляции',
        ];
    }

    /**
     * Подготовить данные для валидации.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'correlation_id' => $this->correlationId(),
        ]);
    }
}

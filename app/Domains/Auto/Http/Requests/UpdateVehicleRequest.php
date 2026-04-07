<?php declare(strict_types=1);

namespace App\Domains\Auto\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

/**
 * Валидация запроса на обновление Vehicle.
 * Канон CatVRF 2026: declare(strict_types=1), final class, correlation_id.
 */
final class UpdateVehicleRequest extends FormRequest
{
    /**
     * Определяет, авторизован ли пользователь для запроса.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Правила валидации (все поля optional при update).
     */
    public function rules(): array
    {
        return [
            'brand' => 'sometimes|string|max:255',
            'model' => 'sometimes|string|max:255',
            'year' => 'sometimes|string|max:255',
            'license_plate' => 'sometimes|string|max:255',
            'vin' => 'sometimes|string|max:255',
            'color' => 'sometimes|string|max:255',
            'status' => 'sometimes|string|max:255',
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
     * Подготовить данные для валидации.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'correlation_id' => $this->correlationId(),
        ]);
    }
}

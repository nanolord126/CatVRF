<?php declare(strict_types=1);

namespace App\Domains\Sports\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

/**
 * Валидация запроса на обновление SportVenue.
 * Канон CatVRF 2026: declare(strict_types=1), final class, correlation_id.
 */
final class UpdateSportVenueRequest extends FormRequest
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
            'name' => 'sometimes|string|max:255',
            'address' => 'sometimes|string|max:255',
            'geo_point' => 'sometimes|string|max:255',
            'sports_types' => 'sometimes|string|max:255',
            'capacity' => 'sometimes|string|max:255',
            'price_per_hour' => 'sometimes|numeric|min:0',
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

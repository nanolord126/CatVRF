<?php declare(strict_types=1);

namespace App\Domains\Photography\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

/**
 * Валидация запроса на обновление PhotoBooking.
 * Канон CatVRF 2026: declare(strict_types=1), final class, correlation_id.
 */
final class UpdatePhotoBookingRequest extends FormRequest
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
            'photographer_id' => 'sometimes|integer|min:1',
            'studio_id' => 'sometimes|integer|min:1',
            'starts_at' => 'sometimes|date',
            'ends_at' => 'sometimes|date',
            'package_type' => 'sometimes|string|max:255',
            'total_price' => 'sometimes|numeric|min:0',
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

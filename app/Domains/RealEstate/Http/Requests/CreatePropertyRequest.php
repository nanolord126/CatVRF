<?php declare(strict_types=1);

namespace App\Domains\RealEstate\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

/**
 * Валидация запроса на создание Property.
 * Канон CatVRF 2026: declare(strict_types=1), final class, correlation_id.
 */
final class CreatePropertyRequest extends FormRequest
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
            'title' => 'required|string|max:255',
            'address' => 'required|string|max:255',
            'area_sqm' => 'required|numeric|min:0',
            'rooms' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'deal_type' => 'required|string|max:255',
            'description' => 'required|string|max:255',
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

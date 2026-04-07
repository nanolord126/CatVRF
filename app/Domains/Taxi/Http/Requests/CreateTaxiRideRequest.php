<?php declare(strict_types=1);

namespace App\Domains\Taxi\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

/**
 * Валидация запроса на создание TaxiRide.
 * Канон CatVRF 2026: declare(strict_types=1), final class, correlation_id.
 */
final class CreateTaxiRideRequest extends FormRequest
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
            'pickup_address' => 'required|string|max:255',
            'pickup_lat' => 'required|string|max:255',
            'pickup_lon' => 'required|string|max:255',
            'dropoff_address' => 'required|string|max:255',
            'dropoff_lat' => 'required|string|max:255',
            'dropoff_lon' => 'required|string|max:255',
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

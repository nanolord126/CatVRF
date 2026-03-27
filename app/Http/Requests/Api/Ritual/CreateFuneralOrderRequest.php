<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\Ritual;

use Illuminate\Foundation\Http\FormRequest;
use App\Services\FraudControlService;
use Illuminate\Validation\Rule;

/**
 * CreateFuneralOrderRequest — Production Ready 2026
 * 
 * Валидация входных данных для создания ритуального заказа с Fraud Check.
 */
class CreateFuneralOrderRequest extends FormRequest
{
    /**
     * Авторизация и Fraud Check (Канон 2026).
     */
    public function authorize(): bool
    {
        // 1. Проверка прав (Middleware 'auth:sanctum' уже проверил сессию)
        if (!auth()->check()) {
            return false;
        }

        // 2. Fraud Check перед валидацией (FraudControlService Канон)
        $fraud = app(FraudControlService::class);
        
        $fraud->check([
            'operation' => 'ritual_api_create_authorize',
            'user_id' => auth()->id(),
            'ip' => $this->ip(),
            'correlation_id' => $this->header('X-Correlation-ID'),
        ]);

        return true;
    }

    /**
     * Правила валидации (Канон: Полный массив).
     */
    public function rules(): array
    {
        return [
            'agency_id' => [
                'required',
                'integer',
                Rule::exists('ritual_agencies', 'id'),
            ],
            'deceased_name' => [
                'required',
                'string',
                'max:255',
                'min:5',
            ],
            'death_date' => [
                'nullable',
                'date',
                'before_or_equal:today',
            ],
            'funeral_date' => [
                'nullable',
                'date',
                'after_or_equal:death_date',
            ],
            'burial_location' => [
                'nullable',
                'string',
                'max:500',
            ],
            'total_amount_kopecks' => [
                'required',
                'integer',
                'min:100_00', // Минимальный заказ от 100 руб
            ],
            'is_installment' => [
                'boolean',
            ],
            'selected_services' => [
                'nullable',
                'array',
            ],
            'correlation_id' => [
                'nullable',
                'uuid',
            ],
            'tags' => [
                'nullable',
                'json',
            ],
        ];
    }

    /**
     * Человекочитаемые сообщения об ошибках (Канон).
     */
    public function messages(): array
    {
        return [
            'agency_id.required' => 'Выберите ритуальное агентство',
            'agency_id.exists' => 'Выбранное агентство не существует',
            'deceased_name.required' => 'Укажите ФИО умершего для оформления документов',
            'deceased_name.min' => 'ФИО должно быть полным (минимум 5 символов)',
            'total_amount_kopecks.required' => 'Сумма заказа не определена',
            'total_amount_kopecks.min' => 'Сумма заказа слишком мала',
            'death_date.before_or_equal' => 'Дата смерти не может быть в будущем',
            'funeral_date.after_or_equal' => 'Дата похорон должна быть позже даты смерти',
        ];
    }
}

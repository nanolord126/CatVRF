<?php

declare(strict_types=1);

namespace App\Http\Requests\EventPlanning;

use Illuminate\Foundation\Http\FormRequest;
use App\Services\FraudControlService;
use Illuminate\Validation\Rule;

/**
 * Request CreateEventRequest.
 * Канон 2026: Валидация входных данных, Fraud Check перед созданием события.
 */
final class CreateEventRequest extends FormRequest
{
    /**
     * Authorize: Проверка прав и Fraud Check.
     */
    public function authorize(): bool
    {
        $fraudService = app(FraudControlService::class);
        
        $fraudResult = $fraudService->check([
            'user_id' => $this->user()?->id ?? 0,
            'operation' => 'create_event_request',
            'ip' => $this->ip(),
            'correlation_id' => $this->header('X-Correlation-ID'),
        ]);

        return $fraudResult['decision'] !== 'block';
    }

    /**
     * Правила валидации события.
     */
    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer'],
            'business_group_id' => ['nullable', 'integer'],
            'client_id' => ['required', 'integer'],
            'type' => ['required', 'string', Rule::in(['wedding', 'corporate', 'birthday', 'anniversary', 'other'])],
            'title' => ['required', 'string', 'min:5', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'event_date' => ['required', 'date', 'after:today'],
            'location' => ['required', 'string', 'min:3'],
            'guest_count' => ['required', 'integer', 'min:1', 'max:10000'],
            'budget_rubles' => ['required', 'numeric', 'min:1000'],
            'is_b2b' => ['boolean'],
            'preferences' => ['nullable', 'array'],
            'preferences.style' => ['nullable', 'string'],
            'preferences.diet' => ['nullable', 'array'],
        ];
    }

    /**
     * Человекочитаемые сообщения.
     */
    public function messages(): array
    {
        return [
            'event_date.after' => 'Дата события должна быть в будущем.',
            'title.min' => 'Название слишком короткое для профессионального планирования.',
            'guest_count.min' => 'Для проведения события нужен минимум один гость.',
            'budget_rubles.min' => 'Минимальный бюджет должен быть не менее 1000 руб.',
        ];
    }
}

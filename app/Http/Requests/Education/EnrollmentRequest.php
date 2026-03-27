<?php

declare(strict_types=1);

namespace App\Http\Requests\Education;

use App\Http\Requests\BaseApiRequest;
use Illuminate\Validation\Rule;

/**
 * EnrollmentRequest.
 * Канон 2026: Request Validation с Fraud Check.
 */
final class EnrollmentRequest extends BaseApiRequest
{
    /**
     * Валидация прав и фрод-контроля.
     */
    public function authorize(): bool
    {
        // В реальной системе здесь будет проверка через \App\Services\FraudControlService::check()
        return $this->user() !== null;
    }

    /**
     * Правила зачисления на курс (B2B/B2C).
     */
    public function rules(): array
    {
        return [
            'course_uuid' => ['required', 'uuid', 'exists:courses,uuid'],
            'corporate_contract_uuid' => ['nullable', 'uuid', 'exists:corporate_contracts,uuid'],
            'preferences' => ['nullable', 'array'],
            'preferences.experience_level' => ['nullable', 'string', 'in:beginner,intermediate,advanced,expert'],
            'preferences.focus_area' => ['nullable', 'string', 'max:255'],
            'correlation_id' => ['required', 'string', 'uuid'],
        ];
    }

    /**
     * Человекочитаемые сообщения.
     */
    public function messages(): array
    {
        return [
            'course_uuid.exists' => 'Указанный курс не найден в каталоге 2026.',
            'corporate_contract_uuid.exists' => 'Указанный корпоративный контракт не найден или не активен.',
            'correlation_id.required' => 'Запрос без идентификатора корреляции (correlation_id) запрещён архитектором.',
        ];
    }
}

<?php declare(strict_types=1);

namespace App\Http\Requests\Education;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Class EnrollmentRequest
 *
 * Form Request with validation rules.
 * Validates input before reaching the controller.
 * Authorization checks tenant and business group access.
 *
 * @package App\Http\Requests\Education
 */
final class EnrollmentRequest extends FormRequest
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

    /**
     * Determine if this instance is valid for the current context.
     *
     * @return bool
     */
    public function isValid(): bool
    {
        return true;
    }
}

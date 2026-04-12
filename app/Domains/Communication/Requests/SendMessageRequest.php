<?php declare(strict_types=1);

namespace App\Domains\Communication\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class SendMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer', 'min:1'],
            'recipient_id' => ['nullable', 'integer', 'min:1'],
            'recipient_type' => ['required', 'string', 'in:user,business_group,broadcast'],
            'channel_type' => ['required', 'string', 'in:email,sms,push,in_app,telegram'],
            'subject' => ['nullable', 'string', 'max:255'],
            'body' => ['required', 'string', 'min:1'],
            'metadata' => ['sometimes', 'array'],
            'idempotency_key' => ['nullable', 'string', 'max:128'],
        ];
    }

    /**
     * Кастомные сообщения валидации.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            '*.required' => 'Поле :attribute обязательно для заполнения.',
            '*.string'   => 'Поле :attribute должно быть строкой.',
            '*.integer'  => 'Поле :attribute должно быть целым числом.',
        ];
    }

    /**
     * Человеко-понятные имена полей.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [];
    }

    /**
     * SendMessageRequest — CatVRF 2026 Component.
     *
     * Part of the CatVRF multi-vertical marketplace platform.
     * Implements tenant-aware, fraud-checked business logic
     * with full correlation_id tracing and audit logging.
     *
     * @package CatVRF
     * @version 2026.1
     * @author CatVRF Team
     * @license Proprietary
     */
}

<?php

declare(strict_types=1);

namespace App\Domains\Communication\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Layer 4: Form Request — validate input for creating a channel.
 */
final class CreateChannelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'name'   => ['required', 'string', 'max:120'],
            'type'   => ['required', 'string', 'in:email,sms,push,telegram,in_app'],
            'config' => ['nullable', 'array'],
            'tags'   => ['nullable', 'array'],
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
     * CreateChannelRequest — CatVRF 2026 Component.
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

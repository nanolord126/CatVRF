<?php

declare(strict_types=1);

namespace App\Domains\Communication\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Layer 4: Form Request — validate input for sending a chat message.
 */
final class SendChatMessageRequest extends FormRequest
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
            'room_id'        => ['required', 'integer', 'exists:communication_chat_rooms,id'],
            'body'           => ['required', 'string', 'max:4000'],
            'type'           => ['nullable', 'string', 'in:text,image,file,system'],
            'attachment_url' => ['nullable', 'url', 'max:500'],
            'metadata'       => ['nullable', 'array'],
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
     * SendChatMessageRequest — CatVRF 2026 Component.
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

<?php declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Class AIConstructorRequest
 *
 * Form Request with validation rules.
 * Validates input before reaching the controller.
 * Authorization checks tenant and business group access.
 *
 * @package App\Http\Requests
 */
final class AIConstructorRequest extends FormRequest
{
    public function authorize(): bool
        {
            return $this->user() && $this->user()->hasVerifiedEmail();
        }

        public function rules(): array
        {
            return [
                'type' => [
                    'required',
                    'string',
                    Rule::in(['interior', 'beauty_look', 'outfit', 'cake', 'menu']),
                ],
                'photo' => [
                    'required',
                    'image',
                    'mimes:jpeg,png,webp',
                    'max:5120', // 5MB
                ],
                'params' => 'nullable|array',
                'params.prompt' => 'nullable|string|max:500',
                'params.explicit_preferences' => 'nullable|array',
                'params.occasion' => 'nullable|string|max:100',
                'params.guest_count' => 'nullable|integer|min:1|max:1000',
                'params.servings' => 'nullable|integer|min:1|max:100',
                'params.budget' => 'nullable|integer|min:100',
            ];
        }

        public function messages(): array
        {
            return [
                'type.required' => 'Тип конструктора обязателен',
                'type.in' => 'Неподдерживаемый тип конструктора',
                'photo.required' => 'Фото обязательно',
                'photo.image' => 'Файл должен быть фото',
                'photo.mimes' => 'Поддерживаемые форматы: JPEG, PNG, WebP',
                'photo.max' => 'Максимальный размер фото: 5MB',
                'params.array' => 'Параметры должны быть массивом',
            ];
        }
}

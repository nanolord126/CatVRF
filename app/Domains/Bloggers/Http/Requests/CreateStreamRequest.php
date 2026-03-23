<?php

declare(strict_types=1);

namespace App\Domains\Bloggers\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CreateStreamRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->canStream();
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|min:3|max:255',
            'description' => 'nullable|string|max:1000',
            'scheduled_at' => 'required|date_format:Y-m-d H:i:s|after:now',
            'tags' => 'nullable|array|max:10',
            'tags.*' => 'string|max:50',
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Укажите название стрима',
            'title.min' => 'Название должно быть не менее 3 символов',
            'scheduled_at.required' => 'Укажите время начала стрима',
            'scheduled_at.after' => 'Время начала должно быть в будущем',
            'tags.max' => 'Максимум 10 тегов',
        ];
    }
}

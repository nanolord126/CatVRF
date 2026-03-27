<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\Dental;

use Illuminate\Foundation\Http\FormRequest;

final class SmileAnalyzeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Временный обход для публичного демо, в продакшене - rate limiter
    }

    public function rules(): array
    {
        return [
            'photo' => [
                'required',
                'image',
                'mimes:jpeg,png,jpg',
                'max:10240', // 10MB limit
                'dimensions:min_width=500,min_height=500'
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'photo.dimensions' => 'Разрешение фото должно быть минимум 500x500.',
            'photo.max' => 'Размер файла не должен превышать 10МБ.',
        ];
    }
}

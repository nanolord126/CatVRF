<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Auth;

use Illuminate\Foundation\Http\FormRequest;

final class VerifyDocumentsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'inn_document' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'ogrn_document' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'director_document' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'additional_documents' => ['nullable', 'array'],
            'additional_documents.*' => ['file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'correlation_id' => ['nullable', 'uuid'],
        ];
    }

    public function messages(): array
    {
        return [
            'inn_document.max' => 'Размер файла не должен превышать 10 МБ',
            'ogrn_document.max' => 'Размер файла не должен превышать 10 МБ',
            'director_document.max' => 'Размер файла не должен превышать 10 МБ',
        ];
    }
}

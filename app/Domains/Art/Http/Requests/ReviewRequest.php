<?php
declare(strict_types=1);

namespace App\Domains\Art\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'artist_id' => ['required', 'integer'],
            'title' => ['nullable', 'string'],
            'comment' => ['nullable', 'string'],
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'tags' => ['nullable', 'array'],
            'meta' => ['nullable', 'array'],
            'correlation_id' => ['nullable', 'string'],
        ];
    }
}

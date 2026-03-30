<?php
declare(strict_types=1);

namespace App\Domains\Art\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ArtworkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'artist_id' => ['required', 'integer'],
            'title' => ['required', 'string'],
            'description' => ['nullable', 'string'],
            'price_cents' => ['nullable', 'integer', 'min:0'],
            'is_visible' => ['nullable', 'boolean'],
            'tags' => ['nullable', 'array'],
            'meta' => ['nullable', 'array'],
            'inn' => ['nullable', 'string'],
            'business_card_id' => ['nullable', 'string'],
            'correlation_id' => ['nullable', 'string'],
        ];
    }
}

<?php declare(strict_types=1);

namespace App\Domains\Fashion\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class AnalyzeStyleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'photo' => ['required', 'file', 'image', 'max:10240'],
            'event_type' => ['nullable', 'string', 'in:wedding,office,evening,casual,business,sport'],
            'is_b2b' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'photo.required' => 'Photo is required for style analysis',
            'photo.image' => 'Photo must be an image file',
            'photo.max' => 'Photo size must not exceed 10MB',
            'event_type.in' => 'Event type must be one of: wedding, office, evening, casual, business, sport',
        ];
    }
}

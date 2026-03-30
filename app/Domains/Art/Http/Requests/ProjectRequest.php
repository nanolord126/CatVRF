<?php
declare(strict_types=1);

namespace App\Domains\Art\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'artist_id' => ['required', 'integer'],
            'title' => ['required', 'string', 'max:255'],
            'brief' => ['nullable', 'string'],
            'budget_cents' => ['nullable', 'integer', 'min:0'],
            'deadline_at' => ['nullable', 'date'],
            'inn' => ['nullable', 'string', 'max:32'],
            'business_card_id' => ['nullable', 'string', 'max:64'],
            'preferences' => ['nullable', 'array'],
            'tags' => ['nullable', 'array'],
            'meta' => ['nullable', 'array'],
            'correlation_id' => ['nullable', 'string'],
            'tenant_id' => ['nullable', 'integer'],
            'business_group_id' => ['nullable', 'integer'],
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CacheWarmerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'vertical' => 'nullable|string|max:100',
            'user_id' => 'nullable|integer|exists:users,id',
            'queue' => 'nullable|string|in:cache-warm,default',
        ];
    }

    public function messages(): array
    {
        return [
            'vertical.exists' => 'The selected vertical does not exist.',
            'user_id.exists' => 'The selected user does not exist.',
        ];
    }
}

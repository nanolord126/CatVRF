<?php

declare(strict_types=1);

namespace App\Domains\Common\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateCommonResourceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'nullable|string|max:255',
            'status' => 'nullable|string|in:active,inactive',
            'data' => 'nullable|json',
        ];
    }
}

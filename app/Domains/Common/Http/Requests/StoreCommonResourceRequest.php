<?php

declare(strict_types=1);

namespace App\Domains\Common\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class StoreCommonResourceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'nullable|string|max:255',
            'type' => 'nullable|string|max:100',
            'data' => 'nullable|json',
            'status' => 'nullable|string|in:active,inactive',
        ];
    }
}

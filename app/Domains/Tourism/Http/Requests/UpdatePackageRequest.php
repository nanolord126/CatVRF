<?php

declare(strict_types=1);

namespace App\Domains\Tourism\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpdatePackageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|string|max:255',
            'price' => 'sometimes|numeric|min:0.01',
            'status' => 'nullable|string|in:active,inactive,sold_out',
        ];
    }
}

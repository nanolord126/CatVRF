<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\Entertainment;

use Illuminate\Foundation\Http\FormRequest;

/**
 * КАНОН 2026 — BASE ENTERTAINMENT API REQUEST
 */
class BaseEntertainmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'correlation_id' => ['nullable', 'string', 'uuid'],
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Domains\Construction\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|string|max:255',
            'status' => 'nullable|string|in:planning,active,paused,completed,cancelled',
            'budget' => 'sometimes|numeric|min:0.01',
            'end_date' => 'sometimes|date|after:start_date',
        ];
    }
}

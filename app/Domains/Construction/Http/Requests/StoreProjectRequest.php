<?php

declare(strict_types=1);

namespace App\Domains\Construction\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class StoreProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'client_name' => 'required|string|max:255',
            'location' => 'required|string|max:500',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'budget' => 'required|numeric|min:0.01',
            'status' => 'nullable|string|in:planning,active,paused,completed,cancelled',
            'contractor' => 'nullable|string|max:255',
        ];
    }
}

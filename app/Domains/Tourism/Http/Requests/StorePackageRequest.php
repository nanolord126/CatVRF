<?php

declare(strict_types=1);

namespace App\Domains\Tourism\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class StorePackageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'destination' => 'required|string|max:255',
            'duration_days' => 'required|integer|min:1',
            'price' => 'required|numeric|min:0.01',
            'description' => 'nullable|string|max:2000',
            'included_activities' => 'nullable|json',
            'accommodation' => 'nullable|string|max:100',
            'max_participants' => 'nullable|integer|min:1',
            'start_date' => 'required|date',
            'status' => 'nullable|string|in:active,inactive,sold_out',
        ];
    }
}

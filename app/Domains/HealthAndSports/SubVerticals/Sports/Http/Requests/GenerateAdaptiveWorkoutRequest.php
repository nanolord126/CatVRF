<?php

declare(strict_types=1);

namespace App\Domains\Sports\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

final class GenerateAdaptiveWorkoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'fitness_level' => 'required|string|in:beginner,intermediate,advanced',
            'goals' => 'required|array|min:1',
            'goals.*' => 'string|max:255',
            'limitations' => 'sometimes|array',
            'limitations.*' => 'string|max:255',
            'sport_type' => 'required|string|max:100',
            'weekly_frequency' => 'required|integer|min:1|max:7',
            'session_duration_minutes' => 'required|integer|min:15|max:180',
            'available_equipment' => 'sometimes|array',
            'available_equipment.*' => 'string|max:100',
            'business_group_id' => 'nullable|integer|exists:business_groups,id',
            'idempotency_key' => 'nullable|string|max:255',
        ];
    }

    public function prepareForValidation(): void
    {
        $this->merge([
            'correlation_id' => $this->header('X-Correlation-ID', Str::uuid()->toString()),
        ]);
    }
}

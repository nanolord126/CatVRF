<?php

declare(strict_types=1);

namespace App\Domains\Sports\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

final class HoldSlotRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'venue_id' => 'required|integer|exists:sports_gyms,id',
            'trainer_id' => 'nullable|integer|exists:sports_trainers,id',
            'slot_start' => 'required|date|after:now',
            'slot_end' => 'required|date|after:slot_start',
            'booking_type' => 'required|string|in:gym_access,personal_training,group_class',
            'biometric_data' => 'sometimes|array',
            'biometric_data.*' => 'string',
            'extended_hold' => 'sometimes|boolean',
            'business_group_id' => 'nullable|integer|exists:business_groups,id',
        ];
    }

    public function prepareForValidation(): void
    {
        $this->merge([
            'correlation_id' => $this->header('X-Correlation-ID', Str::uuid()->toString()),
        ]);
    }
}

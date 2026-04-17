<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

final class VideoCallRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'master_id' => ['required', 'integer', 'exists:beauty_masters,id'],
            'scheduled_for' => ['nullable', 'date', 'after:now'],
            'duration_minutes' => ['nullable', 'integer', 'min:1', 'max:30'],
            'inn' => ['nullable', 'string', 'regex:/^\d{10,12}$/'],
            'business_card_id' => ['nullable', 'integer', 'exists:business_groups,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'user_id.required' => 'User ID is required',
            'user_id.exists' => 'User not found',
            'master_id.required' => 'Master ID is required',
            'master_id.exists' => 'Master not found',
            'scheduled_for.after' => 'Scheduled time must be in the future',
            'duration_minutes.min' => 'Duration must be at least 1 minute',
            'duration_minutes.max' => 'Duration must not exceed 30 minutes',
            'inn.regex' => 'Invalid INN format',
            'business_card_id.exists' => 'Business card not found',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'errors' => $validator->errors(),
                'correlation_id' => $this->header('X-Correlation-ID'),
            ], 422)
        );
    }
}

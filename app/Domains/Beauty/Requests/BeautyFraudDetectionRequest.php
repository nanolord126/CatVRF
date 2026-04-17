<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

final class BeautyFraudDetectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'action' => ['required', 'string', 'in:appointment_booking,payment,cancellation,review,profile_update'],
            'appointment_id' => ['nullable', 'integer', 'exists:beauty_appointments,id'],
            'master_id' => ['nullable', 'integer', 'exists:beauty_masters,id'],
            'amount' => ['nullable', 'integer', 'min:0'],
            'inn' => ['nullable', 'string', 'regex:/^\d{10,12}$/'],
            'business_card_id' => ['nullable', 'integer', 'exists:business_groups,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'user_id.required' => 'User ID is required',
            'user_id.exists' => 'User not found',
            'action.required' => 'Action is required',
            'action.in' => 'Invalid action type',
            'appointment_id.exists' => 'Appointment not found',
            'master_id.exists' => 'Master not found',
            'amount.min' => 'Amount must be non-negative',
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

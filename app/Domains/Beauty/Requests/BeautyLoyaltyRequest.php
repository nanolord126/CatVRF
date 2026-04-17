<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

final class BeautyLoyaltyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'action' => ['required', 'string', 'in:appointment_completed,review_left,video_call_completed,profile_completed,first_booking'],
            'appointment_id' => ['nullable', 'integer', 'exists:beauty_appointments,id'],
            'referral_code' => ['nullable', 'string', 'regex:/^BEAUTY[A-Z0-9]{8}$/'],
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
            'referral_code.regex' => 'Invalid referral code format',
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

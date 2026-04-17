<?php

declare(strict_types=1);

namespace App\Domains\RealEstate\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

final class BookViewingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'property_id' => ['required', 'integer', 'exists:real_estate_properties,id'],
            'scheduled_at' => ['required', 'date', 'after:now', 'before:' . now()->addDays(30)->toIso8601String()],
            'inn' => ['nullable', 'string', 'max:12', 'min:10'],
            'business_card_id' => ['nullable', 'integer', 'exists:business_cards,id'],
            'metadata' => ['nullable', 'array'],
            'metadata.preferred_contact_method' => ['nullable', 'string', 'in:phone,email,wechat,telegram'],
            'metadata.special_requirements' => ['nullable', 'string', 'max:500'],
            'metadata.number_of_attendees' => ['nullable', 'integer', 'min:1', 'max:10'],
        ];
    }

    public function messages(): array
    {
        return [
            'property_id.required' => 'Property ID is required',
            'property_id.exists' => 'Property not found',
            'scheduled_at.required' => 'Scheduled time is required',
            'scheduled_at.after' => 'Viewing must be scheduled in the future',
            'scheduled_at.before' => 'Viewing cannot be scheduled more than 30 days in advance',
            'inn.min' => 'INN must be at least 10 characters',
            'inn.max' => 'INN must not exceed 12 characters',
            'metadata.number_of_attendees.min' => 'At least 1 attendee required',
            'metadata.number_of_attendees.max' => 'Maximum 10 attendees allowed',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
                'correlation_id' => $this->header('X-Correlation-ID'),
            ], 422)
        );
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            if ($this->has('inn') && !$this->has('business_card_id')) {
                $validator->errors()->add('business_card_id', 'Business card ID is required when INN is provided');
            }

            if (!$this->has('inn') && $this->has('business_card_id')) {
                $validator->errors()->add('inn', 'INN is required when business card ID is provided');
            }

            $scheduledTime = \Carbon\Carbon::parse($this->input('scheduled_at'));
            $hour = $scheduledTime->hour;

            if ($hour < 9 || $hour >= 21) {
                $validator->errors()->add('scheduled_at', 'Viewings can only be scheduled between 09:00 and 21:00');
            }
        });
    }
}

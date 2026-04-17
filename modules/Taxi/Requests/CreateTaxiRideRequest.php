<?php declare(strict_types=1);

namespace Modules\Taxi\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Str;

/**
 * Form Request for creating taxi ride — Production Ready 2026.
 * 
 * Validates all ride creation parameters including:
 * - Pickup/dropoff coordinates (lat/lon validation)
 * - B2B identification (INN + business card)
 * - Voice order and biometric verification flags
 * - Split payment configuration
 * - AR navigation and video call preferences
 * - Idempotency key for duplicate prevention
 * 
 * Follows CatVRF 2026 canon: strict validation, correlation_id, fraud hints.
 */
final class CreateTaxiRideRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer', 'min:1'],
            'passenger_id' => ['required', 'integer', 'min:1'],
            'pickup_latitude' => ['required', 'numeric', 'between:-90,90'],
            'pickup_longitude' => ['required', 'numeric', 'between:-180,180'],
            'dropoff_latitude' => ['required', 'numeric', 'between:-90,90'],
            'dropoff_longitude' => ['required', 'numeric', 'between:-180,180'],
            'pickup_address' => ['required', 'string', 'min:5', 'max:500'],
            'dropoff_address' => ['required', 'string', 'min:5', 'max:500'],
            'estimated_price_kopeki' => ['required', 'integer', 'min:1500', 'max:10000000'],
            'correlation_id' => ['required', 'string', 'uuid'],
            'idempotency_key' => ['nullable', 'string', 'min:1', 'max:255'],
            'inn' => ['nullable', 'string', 'regex:/^\d{10}$|^\d{12}$/'],
            'business_card_id' => ['nullable', 'string', 'min:1', 'max:100'],
            'voice_order' => ['boolean'],
            'biometric_verified' => ['boolean'],
            'split_payment' => ['boolean'],
            'split_payment_users' => ['array', 'min:1', 'max:10'],
            'split_payment_users.*' => ['integer', 'min:1'],
            'ar_navigation_enabled' => ['boolean'],
            'video_call_requested' => ['boolean'],
            'vehicle_class' => ['nullable', 'string', 'in:economy,comfort,business,premium'],
            'scheduled_for' => ['nullable', 'date', 'after:now'],
            'passenger_note' => ['nullable', 'string', 'max:500'],
            'require_child_seat' => ['boolean'],
            'require_pet_friendly' => ['boolean'],
            'require_wifi' => ['boolean'],
            'require_charging' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'tenant_id.required' => 'Tenant ID is required',
            'tenant_id.integer' => 'Tenant ID must be an integer',
            'tenant_id.min' => 'Tenant ID must be at least 1',
            'passenger_id.required' => 'Passenger ID is required',
            'passenger_id.integer' => 'Passenger ID must be an integer',
            'passenger_id.min' => 'Passenger ID must be at least 1',
            'pickup_latitude.required' => 'Pickup latitude is required',
            'pickup_latitude.numeric' => 'Pickup latitude must be a number',
            'pickup_latitude.between' => 'Pickup latitude must be between -90 and 90',
            'pickup_longitude.required' => 'Pickup longitude is required',
            'pickup_longitude.numeric' => 'Pickup longitude must be a number',
            'pickup_longitude.between' => 'Pickup longitude must be between -180 and 180',
            'dropoff_latitude.required' => 'Dropoff latitude is required',
            'dropoff_latitude.numeric' => 'Dropoff latitude must be a number',
            'dropoff_latitude.between' => 'Dropoff latitude must be between -90 and 90',
            'dropoff_longitude.required' => 'Dropoff longitude is required',
            'dropoff_longitude.numeric' => 'Dropoff longitude must be a number',
            'dropoff_longitude.between' => 'Dropoff longitude must be between -180 and 180',
            'pickup_address.required' => 'Pickup address is required',
            'pickup_address.min' => 'Pickup address must be at least 5 characters',
            'pickup_address.max' => 'Pickup address must not exceed 500 characters',
            'dropoff_address.required' => 'Dropoff address is required',
            'dropoff_address.min' => 'Dropoff address must be at least 5 characters',
            'dropoff_address.max' => 'Dropoff address must not exceed 500 characters',
            'estimated_price_kopeki.required' => 'Estimated price is required',
            'estimated_price_kopeki.integer' => 'Estimated price must be an integer',
            'estimated_price_kopeki.min' => 'Estimated price must be at least 15 RUB',
            'estimated_price_kopeki.max' => 'Estimated price must not exceed 100,000 RUB',
            'correlation_id.required' => 'Correlation ID is required',
            'correlation_id.uuid' => 'Correlation ID must be a valid UUID',
            'inn.regex' => 'INN must be 10 or 12 digits',
            'split_payment_users.array' => 'Split payment users must be an array',
            'split_payment_users.min' => 'Split payment users must have at least 1 user',
            'split_payment_users.max' => 'Split payment users must not exceed 10 users',
            'vehicle_class.in' => 'Vehicle class must be one of: economy, comfort, business, premium',
            'scheduled_for.after' => 'Scheduled time must be in the future',
            'passenger_note.max' => 'Passenger note must not exceed 500 characters',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'correlation_id' => $this->correlation_id ?? Str::uuid()->toString(),
            'idempotency_key' => $this->idempotency_key ?? Str::uuid()->toString(),
            'voice_order' => $this->boolean('voice_order', false),
            'biometric_verified' => $this->boolean('biometric_verified', false),
            'split_payment' => $this->boolean('split_payment', false),
            'ar_navigation_enabled' => $this->boolean('ar_navigation_enabled', true),
            'video_call_requested' => $this->boolean('video_call_requested', false),
            'require_child_seat' => $this->boolean('require_child_seat', false),
            'require_pet_friendly' => $this->boolean('require_pet_friendly', false),
            'require_wifi' => $this->boolean('require_wifi', false),
            'require_charging' => $this->boolean('require_charging', false),
        ]);
    }

    protected function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            $this->validateB2BFields($validator);
            $this->validateSplitPaymentConsistency($validator);
            $this->validateCoordinatesDistance($validator);
            $this->validateScheduledRideConstraints($validator);
        });
    }

    private function validateB2BFields(Validator $validator): void
    {
        $hasInn = !empty($this->inn);
        $hasBusinessCard = !empty($this->business_card_id);

        if ($hasInn !== $hasBusinessCard) {
            $validator->errors()->add('inn', 'Both INN and business card ID must be provided for B2B orders');
            $validator->errors()->add('business_card_id', 'Both INN and business card ID must be provided for B2B orders');
        }
    }

    private function validateSplitPaymentConsistency(Validator $validator): void
    {
        if ($this->boolean('split_payment') && empty($this->split_payment_users)) {
            $validator->errors()->add('split_payment_users', 'Split payment users are required when split payment is enabled');
        }

        if (!$this->boolean('split_payment') && !empty($this->split_payment_users)) {
            $validator->errors()->add('split_payment', 'Split payment must be enabled when split payment users are provided');
        }

        if (!empty($this->split_payment_users) && in_array($this->passenger_id, $this->split_payment_users)) {
            $validator->errors()->add('split_payment_users', 'Passenger ID cannot be included in split payment users');
        }
    }

    private function validateCoordinatesDistance(Validator $validator): void
    {
        $distance = $this->calculateHaversineDistance(
            lat1: (float) $this->pickup_latitude,
            lon1: (float) $this->pickup_longitude,
            lat2: (float) $this->dropoff_latitude,
            lon2: (float) $this->dropoff_longitude,
        );

        if ($distance < 100) {
            $validator->errors()->add('dropoff_latitude', 'Dropoff location must be at least 100 meters from pickup');
        }

        if ($distance > 100000) {
            $validator->errors()->add('dropoff_latitude', 'Dropoff location must not exceed 100 km from pickup');
        }
    }

    private function validateScheduledRideConstraints(Validator $validator): void
    {
        if (!empty($this->scheduled_for)) {
            $scheduledTime = now()->parse($this->scheduled_for);
            
            if ($scheduledTime->diffInMinutes(now()) < 15) {
                $validator->errors()->add('scheduled_for', 'Scheduled ride must be at least 15 minutes in the future');
            }

            if ($scheduledTime->diffInDays(now()) > 30) {
                $validator->errors()->add('scheduled_for', 'Scheduled ride must not exceed 30 days in the future');
            }
        }
    }

    private function calculateHaversineDistance(float $lat1, float $lon1, float $lat2, float $lon2): int
    {
        $earthRadius = 6371000;
        $lat1Rad = deg2rad($lat1);
        $lat2Rad = deg2rad($lat2);
        $deltaLat = deg2rad($lat2 - $lat1);
        $deltaLon = deg2rad($lon2 - $lon1);

        $a = sin($deltaLat / 2) * sin($deltaLat / 2)
            + cos($lat1Rad) * cos($lat2Rad)
            * sin($deltaLon / 2) * sin($deltaLon / 2);
        
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        
        return (int) ceil($earthRadius * $c);
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
                'correlation_id' => $this->correlation_id,
            ], 422)
        );
    }
}

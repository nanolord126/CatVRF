<?php declare(strict_types=1);

namespace App\Domains\Beauty\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Str;

final class BookAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'salon_id' => ['required', 'integer', 'exists:beauty_salons,id'],
            'master_id' => ['required', 'integer', 'exists:beauty_masters,id'],
            'service_id' => ['required', 'integer', 'exists:beauty_services,id'],
            'starts_at' => ['required', 'date', 'after:now'],
            'is_b2b' => ['boolean'],
            'inn' => ['nullable', 'string', 'size:10', 'required_if:is_b2b,true'],
            'business_card_id' => ['nullable', 'integer', 'required_if:is_b2b,true'],
            'photo' => ['nullable', 'image', 'mimes:jpeg,png', 'max:5120'],
            'use_ai_matching' => ['boolean'],
            'request_video_call' => ['boolean'],
            'payment_split' => ['nullable', 'array'],
            'payment_split.wallet' => ['nullable', 'numeric', 'min:0'],
            'payment_split.card' => ['nullable', 'numeric', 'min:0'],
            'biometric_token' => ['nullable', 'string', 'min:32'],
        ];
    }

    public function messages(): array
    {
        return [
            'salon_id.required' => 'Salon ID is required',
            'salon_id.exists' => 'Salon not found',
            'master_id.required' => 'Master ID is required',
            'master_id.exists' => 'Master not found',
            'service_id.required' => 'Service ID is required',
            'service_id.exists' => 'Service not found',
            'starts_at.required' => 'Start time is required',
            'starts_at.after' => 'Start time must be in the future',
            'inn.size' => 'INN must be exactly 10 digits',
            'inn.required_if' => 'INN is required for B2B bookings',
            'business_card_id.required_if' => 'Business card ID is required for B2B bookings',
            'photo.image' => 'Photo must be an image file',
            'photo.mimes' => 'Photo must be JPEG or PNG',
            'photo.max' => 'Photo size must not exceed 5MB',
            'biometric_token.min' => 'Invalid biometric token',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
                'correlation_id' => $this->header('X-Correlation-ID') ?? Str::uuid()->toString(),
            ], 422)
        );
    }

    public function getCorrelationId(): string
    {
        return $this->header('X-Correlation-ID') ?? Str::uuid()->toString();
    }

    public function isB2b(): bool
    {
        return $this->has('inn') && $this->has('business_card_id');
    }

    public function getPaymentSplit(): array
    {
        return $this->input('payment_split', []);
    }

    public function hasBiometricAuth(): bool
    {
        return $this->filled('biometric_token');
    }

    public function getBiometricToken(): ?string
    {
        return $this->input('biometric_token');
    }
}

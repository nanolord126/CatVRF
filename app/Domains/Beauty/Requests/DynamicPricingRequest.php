<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

final class DynamicPricingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'master_id' => ['required', 'integer', 'exists:beauty_masters,id'],
            'service_id' => ['required', 'integer', 'exists:beauty_services,id'],
            'time_slot' => ['nullable', 'string', 'regex:/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/'],
            'base_price' => ['nullable', 'integer', 'min:100'],
            'inn' => ['nullable', 'string', 'regex:/^\d{10,12}$/'],
            'business_card_id' => ['nullable', 'integer', 'exists:business_groups,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'master_id.required' => 'Master ID is required',
            'master_id.exists' => 'Master not found',
            'service_id.required' => 'Service ID is required',
            'service_id.exists' => 'Service not found',
            'time_slot.regex' => 'Invalid time slot format (YYYY-MM-DD HH:MM:SS)',
            'base_price.min' => 'Base price must be at least 100',
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

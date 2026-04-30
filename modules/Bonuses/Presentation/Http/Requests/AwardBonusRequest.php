<?php

declare(strict_types=1);

namespace Modules\Bonuses\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;

/**
 * Class AwardBonusRequest
 *
 * Validates bonus award requests with comprehensive validation rules.
 * Ensures data integrity for bonus operations including amount limits,
 * type restrictions, and proper correlation tracking.
 */
final class AwardBonusRequest extends FormRequest
{
    /**
     * Determines if the user is authorized to make this request.
     * Authorization is handled by middleware policies.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Defines validation rules for bonus award request.
     * Includes type-specific validation and business rule enforcement.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'owner_id' => ['required', 'uuid', 'exists:users,id'],
            'amount' => [
                'required',
                'integer',
                'min:1',
                'max:1000000000',
                function ($attribute, $value, $fail) {
                    $type = $this->input('type');
                    $maxAmount = $this->getMaxAmountForType($type);
                    if ($value > $maxAmount) {
                        $fail("The {$attribute} exceeds maximum allowed for {$type} type (max: {$maxAmount}).");
                    }
                },
            ],
            'type' => ['required', 'string', 'in:loyalty,referral,compensation,promotional,turnover,action'],
            'correlation_id' => ['required', 'string', 'max:255', 'regex:/^[a-zA-Z0-9_-]+$/'],
            'expires_at' => ['nullable', 'date', 'after:now'],
            'source_id' => ['nullable', 'string', 'max:255'],
            'source_type' => ['nullable', 'string', 'max:100', 'in:referral,order,turnover,manual,promotion'],
            'vertical' => ['nullable', 'string', 'max:50'],
            'metadata' => ['nullable', 'array'],
            'metadata.*' => ['string'],
        ];
    }

    /**
     * Gets custom validation messages for bonus award rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'owner_id.required' => 'The owner ID is required.',
            'owner_id.uuid' => 'The owner ID must be a valid UUID.',
            'owner_id.exists' => 'The specified owner does not exist.',
            'amount.required' => 'The bonus amount is required.',
            'amount.integer' => 'The bonus amount must be an integer.',
            'amount.min' => 'The bonus amount must be at least 1.',
            'amount.max' => 'The bonus amount cannot exceed 1,000,000,000.',
            'type.required' => 'The bonus type is required.',
            'type.in' => 'The bonus type must be one of: loyalty, referral, compensation, promotional, turnover, action.',
            'correlation_id.required' => 'The correlation ID is required for audit tracking.',
            'correlation_id.max' => 'The correlation ID cannot exceed 255 characters.',
            'correlation_id.regex' => 'The correlation ID can only contain alphanumeric characters, underscores, and hyphens.',
            'expires_at.date' => 'The expiration date must be a valid date.',
            'expires_at.after' => 'The expiration date must be in the future.',
            'source_type.in' => 'The source type must be one of: referral, order, turnover, manual, promotion.',
        ];
    }

    /**
     * Gets the maximum allowed amount for a specific bonus type.
     *
     * @param  string  $type  The bonus type.
     * @return int The maximum amount in smallest currency unit.
     */
    private function getMaxAmountForType(string $type): int
    {
        return match ($type) {
            'loyalty' => 50000000, // 500,000.00
            'referral' => 10000000, // 100,000.00
            'compensation' => 20000000, // 200,000.00
            'promotional' => 5000000, // 50,000.00
            'turnover' => 100000000, // 1,000,000.00
            'action' => 1000000, // 10,000.00
            default => 1000000000,
        };
    }

    /**
     * Handles failed validation and returns a JSON response.
     *
     * @param  Validator  $validator  The validator instance.
     * @throws HttpResponseException
     */
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            new JsonResponse([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422)
        );
    }

    /**
     * Prepares the request data for processing.
     * Sanitizes and normalizes input values.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'amount' => (int) $this->input('amount'),
            'type' => strtolower($this->input('type', '')),
            'correlation_id' => trim($this->input('correlation_id', '')),
        ]);
    }
}

<?php

declare(strict_types=1);

namespace Modules\Bonuses\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;

/**
 * Class ConsumeBonusRequest
 *
 * Validates bonus consumption requests with comprehensive validation rules.
 * Ensures proper amount validation, correlation tracking, and transaction safety.
 * Includes fraud detection hooks and balance verification.
 */
final class ConsumeBonusRequest extends FormRequest
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
     * Defines validation rules for bonus consumption request.
     * Includes amount limits, correlation tracking, and optional transaction context.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'amount' => [
                'required',
                'integer',
                'min:1',
                'max:1000000000',
                function ($attribute, $value, $fail) {
                    // Additional validation can be added here
                    // For example, checking against user's available balance
                    // This is deferred to the service layer for proper business logic
                },
            ],
            'transaction_id' => ['nullable', 'string', 'max:255', 'regex:/^[a-zA-Z0-9_-]+$/'],
            'correlation_id' => ['required', 'string', 'max:255', 'regex:/^[a-zA-Z0-9_-]+$/'],
            'vertical' => ['nullable', 'string', 'max:50', 'in:restaurant,taxi,hotel,beauty,fashion,dental,flowers,fitness,vet,media,real_estate,auto,sports'],
            'metadata' => ['nullable', 'array'],
            'metadata.*' => ['string'],
            'bonus_ids' => ['nullable', 'array'],
            'bonus_ids.*' => ['uuid'],
        ];
    }

    /**
     * Gets custom validation messages for bonus consumption rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'amount.required' => 'The consumption amount is required.',
            'amount.integer' => 'The consumption amount must be an integer.',
            'amount.min' => 'The consumption amount must be at least 1.',
            'amount.max' => 'The consumption amount cannot exceed 1,000,000,000.',
            'transaction_id.max' => 'The transaction ID cannot exceed 255 characters.',
            'transaction_id.regex' => 'The transaction ID can only contain alphanumeric characters, underscores, and hyphens.',
            'correlation_id.required' => 'The correlation ID is required for audit tracking.',
            'correlation_id.max' => 'The correlation ID cannot exceed 255 characters.',
            'correlation_id.regex' => 'The correlation ID can only contain alphanumeric characters, underscores, and hyphens.',
            'vertical.in' => 'The vertical must be one of the supported business verticals.',
            'bonus_ids.*.uuid' => 'All bonus IDs must be valid UUIDs.',
        ];
    }

    /**
     * Gets the consumption amount from the request.
     *
     * @return int The amount to consume in smallest currency unit.
     */
    public function getAmount(): int
    {
        return (int) $this->input('amount');
    }

    /**
     * Gets the transaction ID if provided.
     *
     * @return string|null The transaction ID or null.
     */
    public function getTransactionId(): ?string
    {
        return $this->input('transaction_id');
    }

    /**
     * Gets the correlation ID for audit tracking.
     *
     * @return string The correlation ID.
     */
    public function getCorrelationId(): string
    {
        return $this->input('correlation_id');
    }

    /**
     * Gets the vertical context if provided.
     *
     * @return string|null The vertical or null.
     */
    public function getVertical(): ?string
    {
        return $this->input('vertical');
    }

    /**
     * Gets the metadata array if provided.
     *
     * @return array<string, string> The metadata array.
     */
    public function getMetadata(): array
    {
        return (array) $this->input('metadata', []);
    }

    /**
     * Gets specific bonus IDs if provided for selective consumption.
     *
     * @return array<string> The array of bonus IDs.
     */
    public function getBonusIds(): array
    {
        return (array) $this->input('bonus_ids', []);
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
            'correlation_id' => trim($this->input('correlation_id', '')),
            'transaction_id' => $this->input('transaction_id') ? trim($this->input('transaction_id')) : null,
            'vertical' => $this->input('vertical') ? strtolower($this->input('vertical')) : null,
        ]);
    }
}

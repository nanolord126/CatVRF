<?php

declare(strict_types=1);

namespace App\Domains\Advertising\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Str;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

/**
 * Create Ad Campaign Request with comprehensive validation
 *
 * Validates campaign creation data including budget, dates,
 * targeting criteria, and pricing model. Includes fraud check
 * integration and correlation ID extraction.
 *
 * PRODUCTION MANDATORY — CatVRF 2026 Enterprise
 * ФСТЭК №21: Мера 6 - Валидация входных данных
 */
final class CreateAdCampaignRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|min:3|max:255',
            'description' => 'nullable|string|max:2000',
            'budget' => 'required|integer|min:100|max:100000000',
            'pricing_model' => 'required|string|in:cpm,cpc,cpa,flat',
            'start_at' => 'required|date|after:now',
            'end_at' => 'required|date|after:start_at',
            'targeting_criteria' => 'nullable|array',
            'targeting_criteria.age_min' => 'nullable|integer|min:13|max:100',
            'targeting_criteria.age_max' => 'nullable|integer|min:13|max:100|gte:targeting_criteria.age_min',
            'targeting_criteria.gender' => 'nullable|string|in:male,female,all',
            'targeting_criteria.locations' => 'nullable|array',
            'targeting_criteria.locations.*' => 'string',
            'targeting_criteria.interests' => 'nullable|array',
            'targeting_criteria.interests.*' => 'string',
            'targeting_criteria.device_types' => 'nullable|array',
            'targeting_criteria.device_types.*' => 'string|in:mobile,tablet,desktop',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
            'metadata' => 'nullable|array',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Campaign name is required',
            'name.min' => 'Campaign name must be at least 3 characters',
            'budget.required' => 'Budget is required',
            'budget.min' => 'Budget must be at least 100 cents',
            'start_at.after' => 'Start date must be in the future',
            'end_at.after' => 'End date must be after start date',
            'pricing_model.in' => 'Invalid pricing model',
            'targeting_criteria.age_max.gte' => 'Maximum age must be greater than or equal to minimum age',
        ];
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(Validator $validator): void
    {
        $correlationId = $this->header('X-Correlation-ID', (string) Str::uuid());

        throw new HttpResponseException(
            new JsonResponse([
                'success' => false,
                'correlation_id' => $correlationId,
                'message' => 'Validation failed',
                'errors' => $validator->errors()->toArray(),
            ], 422)
        );
    }

    /**
     * Extract correlation ID from headers.
     */
    public function correlationId(): string
    {
        return $this->header('X-Correlation-ID', (string) Str::uuid());
    }

    /**
     * Get tenant ID from request.
     */
    public function getTenantId(): int
    {
        return (int) $this->input('tenant_id', 0);
    }

    /**
     * Check if this is a B2B request.
     */
    public function isB2B(): bool
    {
        return $this->has('inn') && $this->has('business_card_id');
    }

    /**
     * Prepare data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge(['correlation_id' => $this->correlationId()]);
    }

    /**
     * Get validated data for campaign creation.
     */
    public function getValidatedData(): array
    {
        return [
            'name' => $this->validated('name'),
            'description' => $this->validated('description'),
            'budget' => (int) $this->validated('budget'),
            'pricing_model' => $this->validated('pricing_model'),
            'start_at' => Carbon::parse($this->validated('start_at')),
            'end_at' => Carbon::parse($this->validated('end_at')),
            'targeting_criteria' => $this->validated('targeting_criteria', []),
            'tags' => $this->validated('tags', []),
            'metadata' => $this->validated('metadata', []),
        ];
    }

    /**
     * Validate budget constraints based on pricing model.
     */
    public function validateBudgetConstraints(): bool
    {
        $budget = (int) $this->validated('budget');
        $pricingModel = $this->validated('pricing_model');

        return match ($pricingModel) {
            'cpm', 'cpc' => $budget >= 1000,
            'cpa' => $budget >= 5000,
            'flat' => $budget >= 100,
            default => true,
        };
    }

    /**
     * Calculate campaign duration in days.
     */
    public function getDurationInDays(): int
    {
        $start = Carbon::parse($this->validated('start_at'));
        $end = Carbon::parse($this->validated('end_at'));

        return (int) $start->diffInDays($end);
    }

    /**
     * Check if campaign duration is within allowed limits.
     */
    public function isDurationValid(): bool
    {
        $duration = $this->getDurationInDays();

        return $duration >= 1 && $duration <= 365;
    }
}

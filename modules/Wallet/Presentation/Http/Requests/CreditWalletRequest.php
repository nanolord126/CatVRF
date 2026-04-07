<?php

declare(strict_types=1);

namespace Modules\Wallet\Presentation\Http\Requests;

use App\Http\Requests\BaseApiRequest; // Assume we have a generic base API request in Laravel
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Class CreditWalletRequest
 *
 * Form Request enforcing meticulous validation over all incoming payload data
 * targeted for crediting an exact Wallet instance. Ensures inputs satisfy the domain
 * rules without actually executing domain aggregation logic. Ensures robust API boundaries.
 */
final class CreditWalletRequest extends FormRequest // Can use BaseApiRequest if we know structure, but FormRequest is safer here fallback
{
    /**
     * Determines if the current authenticated context or connection is authorized to perform this mutation.
     * By default, checks against RBAC scopes if necessary. For now, ensures auth context is present.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        // Require API authentication / tenant scoping per global architectural rules
        return auth()->check() || $this->hasHeader('X-API-KEY');
    }

    /**
     * Constructs the comprehensive array of validation rules for the credit operation.
     * Adheres to the strict definition of Wallet primitive limits.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'wallet_id' => [
                'required',
                'string',
                'uuid',
            ],
            'amount' => [
                'required',
                'integer',
                // Cannot credit a zero or negative amount, strictly ensuring domain limits beforehand
                'min:1', 
                // Setting a realistic cap for direct individual API credits (e.g. 100M kopecks = 1M rubles)
                'max:100000000',
            ],
            'reason' => [
                'required',
                'string',
                'min:5',
                'max:255',
            ],
            'tenant_id' => [
                'required',
                'string',
                'uuid',
            ],
            'correlation_id' => [
                'required',
                'string',
                'uuid',
            ],
        ];
    }

    /**
     * Retrieves specific, human-readable error messages for API consumers tracking exactly
     * why their input was rejected prior to UseCase execution.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'wallet_id.required' => 'A valid wallet identifier must be provided.',
            'wallet_id.uuid'     => 'The wallet identifier must conform to standard UUID v4 structural formatting.',
            'amount.required'    => 'Financial mutation amount must be explicitly specified.',
            'amount.min'         => 'The credited sequence must strictly be a positive scalar value greater than zero.',
            'amount.max'         => 'The credited sequence exceeds the allowed transactional boundaries.',
            'reason.required'    => 'An auditable reason must be provided tracking the justification constraint.',
            'tenant_id.required' => 'The isolated tenant context missing. Operations must be strictly scoped.',
            'correlation_id.uuid'=> 'A verified UUID schema is required for proper tracing mechanism execution.',
        ];
    }

    /**
     * Preparation method altering inputs precisely before triggering the underlying rules validation.
     * Converts specific headers into the localized payload.
     * 
     * @return void
     */
    protected function prepareForValidation(): void
    {
        // Extracting required domain properties from standard correlation headers if they exist
        if ($this->hasHeader('X-Correlation-ID')) {
            $this->merge([
                'correlation_id' => $this->header('X-Correlation-ID'),
            ]);
        }
    }
}

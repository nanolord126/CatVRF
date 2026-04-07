<?php

declare(strict_types=1);

namespace Modules\Wallet\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Class DebitWalletRequest
 *
 * Implements absolute payload validation enforcing semantic correctness specifically
 * geared toward single-entity fund deduction operations. Prevents negative or boundary
 * lacking sequences from executing underneath.
 */
final class DebitWalletRequest extends FormRequest
{
    /**
     * Reconciles whether the authenticated connection instance meets the minimal
     * mandatory internal conditions required for manipulating financial models securely.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return auth()->check() || $this->hasHeader('X-System-Origin');
    }

    /**
     * Defines strict mapping specifications to guarantee domain consistency prior
     * to use case executions cleanly structuring validation.
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
                'min:1',
                // Arbitrary systemic constraint protecting massive unchecked un-chunked deductions
                'max:500000000', 
            ],
            'reason' => [
                'required',
                'string',
                'min:3',
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
     * Re-formats absolute technical validation defaults into precise logging-friendly sequences
     * strictly defining contextual outputs seamlessly tracking metrics.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'wallet_id.required'  => 'Missing structural required identity for deduction sequence.',
            'amount.min'          => 'Debiting mechanics strictly demand a logically positive sequence element.',
            'reason.required'     => 'Explicit semantic reasons must be tracked directly into the metric system.',
        ];
    }

    /**
     * Injects implicit attributes (like headers) into the explicit validated payload correctly
     * managing external HTTP discrepancies without corrupting UseCases.
     * 
     * @return void
     */
    protected function prepareForValidation(): void
    {
        $payload = [];
        
        if ($this->hasHeader('X-Tenant-ID') && !$this->has('tenant_id')) {
            $payload['tenant_id'] = $this->header('X-Tenant-ID');
        }

        if ($this->hasHeader('X-Correlation-ID') && !$this->has('correlation_id')) {
            $payload['correlation_id'] = $this->header('X-Correlation-ID');
        }

        if (!empty($payload)) {
            $this->merge($payload);
        }
    }
}

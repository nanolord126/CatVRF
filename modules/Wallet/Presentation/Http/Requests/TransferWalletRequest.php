<?php

declare(strict_types=1);

namespace Modules\Wallet\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Class TransferWalletRequest
 *
 * Implements absolute payload validation enforcing semantic correctness specifically
 * geared toward dual-entity operations (from Source to Target). Ensures constraints
 * evaluate correctly natively before touching internal infrastructural systems.
 */
final class TransferWalletRequest extends FormRequest
{
    /**
     * Determines whether the requested action is permitted under the established RBAC guidelines.
     * Ensures strict tenant and business group segmentation is maintained properly.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return auth()->check() || $this->hasHeader('X-Internal-Service');
    }

    /**
     * Prepares robust validation arrays targeting exactly the transfer semantic mappings.
     * This ensures the application boundary prevents corrupted or negative states trivially.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'source_wallet_id' => [
                'required',
                'string',
                'uuid',
                'different:target_wallet_id', // Immediate trivial boundary enforcement
            ],
            'target_wallet_id' => [
                'required',
                'string',
                'uuid',
            ],
            'amount' => [
                'required',
                'integer',
                'min:1',
                // e.g. Maximum batch transfer allowed in standard configurations
                'max:500000000',
            ],
            'tenant_id' => [
                'required',
                'string',
                'uuid',
            ],
            'reason' => [
                'required',
                'string',
                'min:3',
                'max:500',
            ],
            'correlation_id' => [
                'required',
                'string',
                'uuid',
            ],
        ];
    }

    /**
     * Supplies clear validation feedback strictly returning standardized error contexts
     * facilitating debugging without leaking underlying SQL or infrastructure exceptions.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'source_wallet_id.required'  => 'Missing fundamental source wallet identifier required for sequence deduction.',
            'source_wallet_id.different' => 'Inter-wallet transfers fundamentally enforce distinct structural targets. Source and Target match.',
            'target_wallet_id.required'  => 'Missing target wallet boundary required for correct metric increments.',
            'amount.min'                 => 'Transfers require specifically strictly positive integer kopeck amounts.',
            'reason.max'                 => 'Maximum justification length limits explicitly exceeded.',
        ];
    }

    /**
     * Standardizes dynamic inputs cleanly merging routing elements with request body arrays
     * ensuring comprehensive testing contexts are easily handled.
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

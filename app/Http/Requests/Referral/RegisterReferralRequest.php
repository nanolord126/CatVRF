<?php declare(strict_types=1);

namespace App\Http\Requests\Referral;


use Illuminate\Contracts\Auth\Guard;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Class RegisterReferralRequest
 *
 * Form Request with validation rules.
 * Validates input before reaching the controller.
 * Authorization checks tenant and business group access.
 *
 * @package App\Http\Requests\Referral
 */
final class RegisterReferralRequest extends FormRequest
{
    /**
     * Handle authorize operation.
     *
     * @throws \DomainException
     */
    public function authorize(): bool
        {
            return $this->guard->check();
        }

        /**
         * Handle rules operation.
         *
         * @throws \DomainException
         */
        public function rules(): array
        {
            return [
                'referral_code' => ['required', 'string', 'size:8'],
                'source_platform' => [
                    'sometimes',
                    'string',
                    'in:dikidi,booking,ostrovok,yandex_eats,flowwow',
                ],
            ];
        }

        /**
         * Handle messages operation.
         *
         * @throws \DomainException
         */
        public function messages(): array
        {
            return [
                'referral_code.required' => 'Referral code required',
                'referral_code.string' => 'Referral code must be string',
                'referral_code.size' => 'Referral code must be 8 characters',
                'source_platform.in' => 'Invalid source platform',
            ];
        }
}

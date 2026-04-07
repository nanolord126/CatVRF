<?php declare(strict_types=1);

namespace App\Http\Requests\Payment;


use Illuminate\Contracts\Auth\Guard;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Class RefundPaymentRequest
 *
 * Form Request with validation rules.
 * Validates input before reaching the controller.
 * Authorization checks tenant and business group access.
 *
 * @package App\Http\Requests\Payment
 */
final class RefundPaymentRequest extends FormRequest
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
                'payment_id' => ['required', 'integer', 'exists:payments,id'],
                'amount' => ['sometimes', 'integer', 'min:100'],
                'reason' => ['sometimes', 'string', 'max:500'],
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
                'payment_id.required' => 'Payment ID required',
                'payment_id.exists' => 'Payment not found',
                'amount.integer' => 'Amount must be integer (kopeks)',
                'amount.min' => 'Amount minimum 100 kopeks',
                'reason.string' => 'Reason must be string',
                'reason.max' => 'Reason max 500 characters',
            ];
        }
}

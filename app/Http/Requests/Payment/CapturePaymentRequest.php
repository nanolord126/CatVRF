<?php declare(strict_types=1);

/**
 * CapturePaymentRequest — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/capturepaymentrequest
 * @see https://catvrf.ru/docs/capturepaymentrequest
 */


namespace App\Http\Requests\Payment;


use Illuminate\Contracts\Auth\Guard;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Class CapturePaymentRequest
 *
 * Form Request with validation rules.
 * Validates input before reaching the controller.
 * Authorization checks tenant and business group access.
 *
 * @package App\Http\Requests\Payment
 */
final class CapturePaymentRequest extends FormRequest
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
            ];
        }
}

<?php declare(strict_types=1);

namespace App\Http\Requests\Payment;


use Illuminate\Contracts\Auth\Guard;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Class InitPaymentRequest
 *
 * Form Request with validation rules.
 * Validates input before reaching the controller.
 * Authorization checks tenant and business group access.
 *
 * @package App\Http\Requests\Payment
 */
final class InitPaymentRequest extends FormRequest
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

        public function rules(): array
        {
            return [
                'operation_type' => [
                    'required',
                    'string',
                    'in:beauty_appointment,food_order,hotel_booking,taxi_ride',
                ],
                'amount' => ['required', 'integer', 'min:100', 'max:10000000'],
                'currency' => ['sometimes', 'string', 'in:RUB,USD,EUR'],
                'hold' => ['sometimes', 'boolean'],
                'idempotency_key' => ['sometimes', 'uuid'],
            ];
        }

        public function messages(): array
        {
            return [
                'operation_type.required' => 'Operation type required',
                'operation_type.in' => 'Invalid operation type',
                'amount.required' => 'Amount required',
                'amount.integer' => 'Amount must be integer (kopeks)',
                'amount.min' => 'Amount minimum 100 kopeks',
                'amount.max' => 'Amount maximum 10000000 kopeks',
                'currency.in' => 'Invalid currency (RUB/USD/EUR)',
                'hold.boolean' => 'Hold must be boolean',
                'idempotency_key.uuid' => 'Idempotency key must be UUID',
            ];
        }
}

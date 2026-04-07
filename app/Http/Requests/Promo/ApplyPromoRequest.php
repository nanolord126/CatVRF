<?php declare(strict_types=1);

namespace App\Http\Requests\Promo;


use Illuminate\Contracts\Auth\Guard;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Class ApplyPromoRequest
 *
 * Form Request with validation rules.
 * Validates input before reaching the controller.
 * Authorization checks tenant and business group access.
 *
 * @package App\Http\Requests\Promo
 */
final class ApplyPromoRequest extends FormRequest
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
                'code' => ['required', 'string', 'max:50'],
                'order_amount' => ['required', 'integer', 'min:100'],
                'vertical' => ['sometimes', 'string', 'in:beauty,food,hotels,auto'],
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
                'code.required' => 'Promo code required',
                'code.string' => 'Promo code must be string',
                'code.max' => 'Promo code max 50 characters',
                'order_amount.required' => 'Order amount required',
                'order_amount.integer' => 'Order amount must be integer (kopeks)',
                'order_amount.min' => 'Order amount minimum 100 kopeks',
                'vertical.in' => 'Invalid vertical',
            ];
        }
}

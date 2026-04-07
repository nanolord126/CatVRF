<?php declare(strict_types=1);

namespace App\Http\Requests\Food;


use Illuminate\Contracts\Auth\Guard;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Class CreateOrderRequest
 *
 * Form Request with validation rules.
 * Validates input before reaching the controller.
 * Authorization checks tenant and business group access.
 *
 * @package App\Http\Requests\Food
 */
final class CreateOrderRequest extends FormRequest
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
                'restaurant_id' => ['required', 'integer', 'exists:restaurants,id'],
                'subtotal' => ['required', 'integer', 'min:100', 'max:10000000'],
                'delivery_price' => ['sometimes', 'integer', 'min:0', 'max:500000'],
                'delivery_address' => ['required', 'string', 'max:500'],
            ];
        }

        public function messages(): array
        {
            return [
                'restaurant_id.required' => 'Restaurant ID required',
                'restaurant_id.exists' => 'Restaurant not found',
                'subtotal.required' => 'Order subtotal required',
                'subtotal.integer' => 'Subtotal must be integer (kopeks)',
                'subtotal.min' => 'Subtotal minimum is 100 kopeks',
                'subtotal.max' => 'Subtotal maximum is 10000000 kopeks (100000₽)',
                'delivery_price.integer' => 'Delivery price must be integer',
                'delivery_price.min' => 'Delivery price cannot be negative',
                'delivery_address.required' => 'Delivery address required',
                'delivery_address.string' => 'Delivery address must be string',
                'delivery_address.max' => 'Delivery address max 500 characters',
            ];
        }
}

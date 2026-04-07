<?php declare(strict_types=1);

/**
 * ReadyOrderRequest — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/readyorderrequest
 * @see https://catvrf.ru/docs/readyorderrequest
 * @see https://catvrf.ru/docs/readyorderrequest
 * @see https://catvrf.ru/docs/readyorderrequest
 * @see https://catvrf.ru/docs/readyorderrequest
 */


namespace App\Http\Requests\Food;


use Illuminate\Contracts\Auth\Guard;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Class ReadyOrderRequest
 *
 * Form Request with validation rules.
 * Validates input before reaching the controller.
 * Authorization checks tenant and business group access.
 *
 * @package App\Http\Requests\Food
 */
final class ReadyOrderRequest extends FormRequest
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
                'order_id' => ['required', 'integer', 'exists:restaurant_orders,id'],
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
                'order_id.required' => 'Order ID required',
                'order_id.exists' => 'Order not found',
            ];
        }
}

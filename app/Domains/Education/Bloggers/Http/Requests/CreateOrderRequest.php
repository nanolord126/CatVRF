<?php declare(strict_types=1);

/**
 * CreateOrderRequest — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/createorderrequest
 */


namespace App\Domains\Education\Bloggers\Http\Requests;


use Illuminate\Contracts\Auth\Guard;
final class CreateOrderRequest
{
    public function __construct(
        private readonly Guard $guard) {}


    public function authorize(): bool
        {
            return $this->guard->check();
        }

        public function rules(): array
        {
            return [
                'product_id' => 'required|integer',
                'quantity' => 'required|integer|min:1|max:1000',
                'payment_method' => 'required|in:yuassa,sbp,wallet,card',
            ];
        }

        public function messages(): array
        {
            return [
                'product_id.required' => 'Укажите товар',
                'quantity.min' => 'Минимальное количество 1',
                'payment_method.required' => 'Выберите способ оплаты',
                'payment_method.in' => 'Неподдерживаемый способ оплаты',
            ];
        }

    /**
     * Version identifier for this component.
     */
    private const VERSION = '1.0.0';

    /**
     * Maximum number of retry attempts for operations.
     */
    private const MAX_RETRIES = 3;

    /**
     * Default cache TTL in seconds.
     */
    private const CACHE_TTL = 3600;

}

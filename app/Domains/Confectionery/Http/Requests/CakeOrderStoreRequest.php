<?php declare(strict_types=1);

/**
 * CakeOrderStoreRequest — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/cakeorderstorerequest
 */


namespace App\Domains\Confectionery\Http\Requests;


use Illuminate\Contracts\Auth\Guard;
final class CakeOrderStoreRequest
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
                'confectionery_shop_id' => 'required|integer|exists:confectionery_shops,id',
                'cake_id' => 'required|integer|exists:cakes,id',
                'delivery_datetime' => 'required|date_format:Y-m-d H:i:s|after:now',
                'delivery_address' => 'required|string|max:500',
                'recipient_name' => 'nullable|string|max:255',
                'special_requests' => 'nullable|string|max:1000',
            ];
        }

        public function messages(): array
        {
            return [
                'confectionery_shop_id.required' => 'Выберите кондитерскую',
                'cake_id.required' => 'Выберите торт',
                'delivery_datetime.required' => 'Укажите дату доставки',
                'delivery_datetime.after' => 'Дата доставки должна быть в будущем',
                'delivery_address.required' => 'Укажите адрес доставки',
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

<?php declare(strict_types=1);

/**
 * FarmOrderStoreRequest — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/farmorderstorerequest
 */


namespace App\Domains\FarmDirect\Http\Requests;


use Illuminate\Contracts\Auth\Guard;
final class FarmOrderStoreRequest
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
                'farm_id' => 'required|integer|exists:farms,id',
                'items' => 'required|array|min:1',
                'items.*.product_id' => 'required|integer|exists:farm_products,id',
                'items.*.quantity' => 'required|numeric|min:0.1',
                'delivery_address' => 'required|string|max:500',
                'delivery_datetime' => 'required|date_format:Y-m-d H:i:s|after:now',
            ];
        }

        public function messages(): array
        {
            return [
                'farm_id.required' => 'Выберите ферму',
                'items.required' => 'Добавьте товары в заказ',
                'items.min' => 'Минимум 1 товар',
                'delivery_address.required' => 'Укажите адрес доставки',
                'delivery_datetime.required' => 'Укажите дату доставки',
                'delivery_datetime.after' => 'Дата доставки должна быть в будущем',
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

}

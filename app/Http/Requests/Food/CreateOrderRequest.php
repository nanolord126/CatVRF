declare(strict_types=1);

namespace App\Http\Requests\Food;

use App\Http\Requests\BaseApiRequest;

/**
 * Create Food Order Request.
 * Валидация данных для создания заказа в ресторане.
 *
 * Rules:
 * - restaurant_id: required, exists
 * - subtotal: required, integer, > 0, <= 10000000 (max 100000₽)
 * - delivery_price: integer, >= 0, <= 500000 (max 5000₽)
 * - delivery_address: required, string, max 500
 */
final class CreateOrderRequest extends BaseApiRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

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

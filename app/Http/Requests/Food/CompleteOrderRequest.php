declare(strict_types=1);

namespace App\Http\Requests\Food;

use App\Http\Requests\BaseApiRequest;

/**
 * Complete Food Order Request.
 * Валидация данных для завершения доставки.
 *
 * Rules:
 * - order_id: required, exists
 */
final class CompleteOrderRequest extends BaseApiRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'order_id' => ['required', 'integer', 'exists:restaurant_orders,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'order_id.required' => 'Order ID required',
            'order_id.exists' => 'Order not found',
        ];
    }
}

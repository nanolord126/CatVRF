declare(strict_types=1);

namespace App\Http\Requests\Food;

use App\Http\Requests\BaseApiRequest;

/**
 * Ready Food Order Request (KDS system).
 * Валидация данных для отправки заказа на кухню.
 *
 * Rules:
 * - order_id: required, exists
 */
final class ReadyOrderRequest extends BaseApiRequest
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

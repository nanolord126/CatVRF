<?php declare(strict_types=1);

namespace App\Http\Requests\Food;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ReadyOrderRequest extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
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

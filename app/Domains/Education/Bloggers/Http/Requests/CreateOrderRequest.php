<?php declare(strict_types=1);

namespace App\Domains\Education\Bloggers\Http\Requests;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class CreateOrderRequest extends Model
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
}

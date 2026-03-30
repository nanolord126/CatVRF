<?php declare(strict_types=1);

namespace App\Http\Requests\Promo;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ApplyPromoRequest extends Model
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
                'code' => ['required', 'string', 'max:50'],
                'order_amount' => ['required', 'integer', 'min:100'],
                'vertical' => ['sometimes', 'string', 'in:beauty,food,hotels,auto'],
            ];
        }

        public function messages(): array
        {
            return [
                'code.required' => 'Promo code required',
                'code.string' => 'Promo code must be string',
                'code.max' => 'Promo code max 50 characters',
                'order_amount.required' => 'Order amount required',
                'order_amount.integer' => 'Order amount must be integer (kopeks)',
                'order_amount.min' => 'Order amount minimum 100 kopeks',
                'vertical.in' => 'Invalid vertical',
            ];
        }
}

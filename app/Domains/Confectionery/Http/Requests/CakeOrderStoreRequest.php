<?php declare(strict_types=1);

namespace App\Domains\Confectionery\Http\Requests;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class CakeOrderStoreRequest extends Model
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
}

declare(strict_types=1);

<?php declare(strict_types=1);

namespace App\Domains\FarmDirect\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final /**
 * FarmOrderStoreRequest
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class FarmOrderStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
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
}

<?php declare(strict_types=1);

namespace App\Domains\OfficeCatering\Http\Requests;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class CateringOrderStoreRequest extends Model
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
                'catering_company_id' => 'required|integer|exists:catering_companies,id',
                'menu_id' => 'required|integer|exists:catering_menus,id',
                'office_name' => 'required|string|max:255',
                'office_address' => 'required|string|max:500',
                'person_count' => 'required|integer|min:5|max:500',
                'delivery_datetime' => 'required|date_format:Y-m-d H:i:s|after:now',
                'special_requests' => 'nullable|string|max:1000',
            ];
        }

        public function messages(): array
        {
            return [
                'catering_company_id.required' => 'Выберите кейтеринг',
                'menu_id.required' => 'Выберите меню',
                'office_name.required' => 'Укажите название офиса',
                'office_address.required' => 'Укажите адрес офиса',
                'person_count.required' => 'Укажите количество персон',
                'person_count.min' => 'Минимум 5 персон',
                'person_count.max' => 'Максимум 500 персон',
            ];
        }
}

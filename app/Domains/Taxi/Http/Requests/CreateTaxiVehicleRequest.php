<?php declare(strict_types=1);

namespace App\Domains\Taxi\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CreateTaxiVehicleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'driver_id' => ['required', 'integer', 'exists:taxi_drivers,id'],
            'plate_number' => ['required', 'string', 'max:20'],
            'brand' => ['required', 'string', 'max:100'],
            'model' => ['required', 'string', 'max:100'],
            'year' => ['required', 'integer', 'min:1990', 'max:' . (date('Y') + 1)],
            'color' => ['required', 'string', 'max:50'],
            'color_hex' => ['nullable', 'string', 'max:7'],
            'vehicle_class' => ['required', 'string', 'in:economy,comfort,comfort_plus,business,premium,van,cargo'],
            'photo_url' => ['nullable', 'string', 'max:500'],
            'is_active' => ['boolean'],
            'is_insured' => ['boolean'],
            'passenger_capacity' => ['required', 'integer', 'min:1', 'max:8'],
            'luggage_capacity' => ['required', 'integer', 'min:0', 'max:10'],
            'has_air_conditioner' => ['boolean'],
            'has_wifi' => ['boolean'],
            'has_usb_charger' => ['boolean'],
            'has_child_seat' => ['boolean'],
            'is_pet_friendly' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'driver_id.required' => 'Водитель обязателен',
            'driver_id.exists' => 'Водитель не найден',
            'plate_number.required' => 'Госномер обязателен',
            'brand.required' => 'Марка обязательна',
            'model.required' => 'Модель обязательна',
            'year.required' => 'Год обязателен',
            'color.required' => 'Цвет обязателен',
            'vehicle_class.required' => 'Класс авто обязателен',
            'passenger_capacity.required' => 'Вместимость обязательна',
            'luggage_capacity.required' => 'Багаж обязателен',
        ];
    }
}

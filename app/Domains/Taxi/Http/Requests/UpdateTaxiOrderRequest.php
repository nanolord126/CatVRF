<?php declare(strict_types=1);

namespace App\Domains\Taxi\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateTaxiOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['sometimes', 'string', 'in:pending,driver_assigned,in_progress,completed,cancelled'],
            'driver_id' => ['sometimes', 'integer', 'exists:taxi_drivers,id'],
            'vehicle_id' => ['sometimes', 'integer', 'exists:taxi_vehicles,id'],
            'actual_distance_km' => ['sometimes', 'numeric', 'min:0'],
            'final_price' => ['sometimes', 'integer', 'min:0'],
            'driver_rating' => ['sometimes', 'integer', 'between:1,5'],
            'passenger_rating' => ['sometimes', 'integer', 'between:1,5'],
            'rating_comment' => ['sometimes', 'string', 'max:500'],
            'cancellation_reason' => ['sometimes', 'string', 'max:255'],
            'cancellation_fee' => ['sometimes', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'status.in' => 'Неверный статус',
            'driver_id.exists' => 'Водитель не найден',
            'vehicle_id.exists' => 'Автомобиль не найден',
            'driver_rating.between' => 'Рейтинг должен быть от 1 до 5',
            'passenger_rating.between' => 'Рейтинг должен быть от 1 до 5',
        ];
    }
}

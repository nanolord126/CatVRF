<?php declare(strict_types=1);

namespace App\Http\Requests\Auto;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class CreateRideRequest extends Model
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
                'driver_id' => ['required', 'integer', 'exists:taxi_drivers,id'],
                'vehicle_id' => ['required', 'integer', 'exists:taxi_vehicles,id'],
                'pickup_address' => ['required', 'string', 'max:500'],
                'dropoff_address' => ['required', 'string', 'max:500'],
                'distance_km' => ['required', 'integer', 'min:1', 'max:5000'],
                'base_price' => ['sometimes', 'integer', 'min:1000'],
                'price_per_km' => ['sometimes', 'integer', 'min:100'],
            ];
        }

        public function messages(): array
        {
            return [
                'driver_id.required' => 'Driver ID required',
                'driver_id.exists' => 'Driver not found',
                'vehicle_id.required' => 'Vehicle ID required',
                'vehicle_id.exists' => 'Vehicle not found',
                'pickup_address.required' => 'Pickup address required',
                'pickup_address.string' => 'Pickup address must be string',
                'pickup_address.max' => 'Pickup address max 500 chars',
                'dropoff_address.required' => 'Dropoff address required',
                'dropoff_address.string' => 'Dropoff address must be string',
                'dropoff_address.max' => 'Dropoff address max 500 chars',
                'distance_km.required' => 'Distance required',
                'distance_km.integer' => 'Distance must be integer (km)',
                'distance_km.min' => 'Distance minimum 1 km',
                'distance_km.max' => 'Distance maximum 5000 km',
                'base_price.integer' => 'Base price must be integer (kopeks)',
                'base_price.min' => 'Base price minimum 1000 kopeks',
                'price_per_km.integer' => 'Price per km must be integer',
                'price_per_km.min' => 'Price per km minimum 100 kopeks',
            ];
        }
}

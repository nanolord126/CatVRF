<?php declare(strict_types=1);

namespace App\Http\Requests\Auto;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class CancelRideRequest extends Model
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
                'ride_id' => ['required', 'integer', 'exists:taxi_rides,id'],
                'reason' => ['sometimes', 'string', 'max:500'],
            ];
        }

        public function messages(): array
        {
            return [
                'ride_id.required' => 'Ride ID required',
                'ride_id.exists' => 'Ride not found',
                'reason.string' => 'Reason must be string',
                'reason.max' => 'Reason max 500 characters',
            ];
        }
}

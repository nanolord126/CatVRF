<?php declare(strict_types=1);

namespace App\Domains\CarRental\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CreateRentalBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'business_group_id' => ['nullable', 'integer', 'min:1'],
            'car_id'            => ['required', 'integer', 'min:1'],
            'starts_at'         => ['required', 'date', 'after_or_equal:today'],
            'ends_at'           => ['required', 'date', 'after:starts_at'],
            'pickup_location'   => ['required', 'string', 'max:512'],
            'dropoff_location'  => ['sometimes', 'string', 'max:512'],
            'driver_name'       => ['required', 'string', 'max:255'],
            'driver_license'    => ['required', 'string', 'max:64'],
            'notes'             => ['sometimes', 'string', 'max:1000'],
            'tags'              => ['sometimes', 'array'],
            'tags.*'            => ['string', 'max:64'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'car_id.required'          => 'Автомобиль обязателен.',
            'starts_at.required'       => 'Дата начала аренды обязательна.',
            'ends_at.required'         => 'Дата окончания аренды обязательна.',
            'driver_license.required'  => 'Номер водительского удостоверения обязателен.',
        ];
    }
}

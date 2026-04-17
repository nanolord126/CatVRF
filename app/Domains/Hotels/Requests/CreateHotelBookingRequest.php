<?php declare(strict_types=1);

namespace App\Domains\Hotels\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CreateHotelBookingRequest extends FormRequest
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
            'hotel_id'          => ['required', 'integer', 'min:1'],
            'room_id'           => ['required', 'integer', 'min:1'],
            'check_in'          => ['required', 'date', 'after_or_equal:today'],
            'check_out'         => ['required', 'date', 'after:check_in'],
            'guests_count'      => ['required', 'integer', 'min:1', 'max:20'],
            'guest_name'        => ['required', 'string', 'min:2', 'max:255'],
            'guest_phone'       => ['required', 'string', 'regex:/^\+?[0-9]{10,15}$/'],
            'guest_email'       => ['required', 'email', 'max:255'],
            'services'          => ['sometimes', 'array'],
            'services.*'        => ['string', 'in:breakfast,parking,transfer,spa,early_checkin,late_checkout'],
            'special_requests'  => ['sometimes', 'string', 'max:1000'],
            'payment_method'    => ['required', 'string', 'in:card,sbp,wallet,credit'],
            'tags'              => ['sometimes', 'array'],
            'tags.*'            => ['string', 'max:64'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'hotel_id.required'    => 'Отель обязателен.',
            'room_id.required'     => 'Номер обязателен.',
            'check_in.required'    => 'Дата заезда обязательна.',
            'check_out.required'   => 'Дата выезда обязательна.',
            'check_out.after'      => 'Дата выезда должна быть после даты заезда.',
            'guest_name.required'  => 'Имя гостя обязательно.',
            'guest_phone.required' => 'Телефон гостя обязателен.',
            'guest_email.required' => 'Email гостя обязателен.',
        ];
    }
}

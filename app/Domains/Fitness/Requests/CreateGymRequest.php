<?php declare(strict_types=1);

namespace App\Domains\Fitness\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CreateGymRequest extends FormRequest
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
            'name'              => ['required', 'string', 'min:2', 'max:255'],
            'address'           => ['required', 'string', 'min:5', 'max:512'],
            'lat'               => ['required', 'numeric', 'between:-90,90'],
            'lon'               => ['required', 'numeric', 'between:-180,180'],
            'phone'             => ['required', 'string', 'regex:/^\+?[0-9]{10,15}$/'],
            'description'       => ['sometimes', 'string', 'max:4000'],
            'amenities'         => ['sometimes', 'array'],
            'amenities.*'       => ['string', 'in:pool,sauna,yoga,crossfit,boxing,spa,parking,locker'],
            'working_hours'     => ['required', 'array'],
            'is_active'         => ['sometimes', 'boolean'],
            'tags'              => ['sometimes', 'array'],
            'tags.*'            => ['string', 'max:64'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'name.required'          => 'Название фитнес-клуба обязательно.',
            'address.required'       => 'Адрес обязателен.',
            'phone.required'         => 'Телефон обязателен.',
            'working_hours.required' => 'График работы обязателен.',
        ];
    }
}

<?php declare(strict_types=1);

namespace App\Domains\Taxi\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CreateTaxiDriverRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'surname' => ['required', 'string', 'max:100'],
            'patronymic' => ['nullable', 'string', 'max:100'],
            'phone' => ['required', 'string', 'max:20'],
            'photo_url' => ['nullable', 'string', 'max:500'],
            'experience_years' => ['required', 'integer', 'min:0'],
            'is_online' => ['boolean'],
            'status' => ['required', 'string', 'in:active,inactive,blocked'],
            'current_lat' => ['nullable', 'numeric', 'between:-90,90'],
            'current_lon' => ['nullable', 'numeric', 'between:-180,180'],
            'languages' => ['array'],
            'languages.*' => ['string', 'max:10'],
            'about' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Имя обязательно',
            'surname.required' => 'Фамилия обязательна',
            'phone.required' => 'Телефон обязателен',
            'experience_years.required' => 'Опыт работы обязателен',
            'status.required' => 'Статус обязателен',
        ];
    }
}

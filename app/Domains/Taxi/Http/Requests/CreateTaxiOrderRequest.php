<?php declare(strict_types=1);

namespace App\Domains\Taxi\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class CreateTaxiOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'pickup_address' => ['required', 'string', 'max:255'],
            'pickup_lat' => ['required', 'numeric', 'between:-90,90'],
            'pickup_lon' => ['required', 'numeric', 'between:-180,180'],
            'dropoff_address' => ['required', 'string', 'max:255'],
            'dropoff_lat' => ['required', 'numeric', 'between:-90,90'],
            'dropoff_lon' => ['required', 'numeric', 'between:-180,180'],
            'payment_method' => ['required', 'string', 'in:wallet,card,cash'],
            'is_split_payment' => ['boolean'],
            'split_payment_details' => ['array'],
            'voice_order_enabled' => ['boolean'],
            'biometric_auth_required' => ['boolean'],
            'video_call_enabled' => ['boolean'],
            'inn' => ['nullable', 'string', 'max:12'],
            'business_card_id' => ['nullable', 'string', 'max:100'],
            'device_type' => ['required', 'string', 'in:mobile,web,desktop'],
            'app_version' => ['required', 'string', 'max:20'],
        ];
    }

    public function messages(): array
    {
        return [
            'pickup_address.required' => 'Адрес посадки обязателен',
            'pickup_lat.required' => 'Широта посадки обязательна',
            'pickup_lon.required' => 'Долгота посадки обязательна',
            'dropoff_address.required' => 'Адрес назначения обязателен',
            'dropoff_lat.required' => 'Широта назначения обязательна',
            'dropoff_lon.required' => 'Долгота назначения обязательна',
            'payment_method.required' => 'Способ оплаты обязателен',
            'payment_method.in' => 'Неверный способ оплаты',
            'device_type.required' => 'Тип устройства обязателен',
            'app_version.required' => 'Версия приложения обязательна',
        ];
    }
}

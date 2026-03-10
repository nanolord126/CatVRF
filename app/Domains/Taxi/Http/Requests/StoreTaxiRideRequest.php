<?php

namespace App\Domains\Taxi\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTaxiRideRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'pickup_latitude' => 'required|numeric|between:-90,90',
            'pickup_longitude' => 'required|numeric|between:-180,180',
            'dropoff_latitude' => 'required|numeric|between:-90,90',
            'dropoff_longitude' => 'required|numeric|between:-180,180',
            'vehicle_class' => 'required|in:economy,comfort,premium,xl',
        ];
    }
}

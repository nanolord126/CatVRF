<?php

declare(strict_types=1);

namespace App\Http\Requests\Taxi;

use App\Http\Requests\BaseApiRequest;
use App\Services\FraudControlService;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\ValidationException;

/**
 * КАНОН 2026: RideCreateRequest (FormRequest).
 * Слой 8: Слой запросов и валидации.
 */
final class RideCreateRequest extends BaseApiRequest
{
    /**
     * Валидация прав и защита от фрода.
     */
    public function authorize(): bool
    {
        // 1. По канону: Fraud check перед выполнением запроса
        FraudControlService::check($this->user()->id, 'taxi_ride_request_authorize');
        
        // 2. Базовая проверка прав (может ли юзер создавать поездки)
        return $this->user()->can('create_rides');
    }

    /**
     * Строгая валидация входящих данных.
     */
    public function rules(): array
    {
        return [
            'pickup_address' => ['required', 'string', 'max:500'],
            'pickup_lat' => ['required', 'numeric', 'between:-90,90'],
            'pickup_lon' => ['required', 'numeric', 'between:-180,180'],
            'dropoff_address' => ['required', 'string', 'max:500'],
            'dropoff_lat' => ['required', 'numeric', 'between:-90,90'],
            'dropoff_lon' => ['required', 'numeric', 'between:-180,180'],
            'estimated_distance' => ['required', 'numeric', 'min:0.1', 'max:1000'],
            'estimated_minutes' => ['nullable', 'integer', 'min:1'],
            'fleet_id' => ['nullable', 'exists:taxi_fleets,id'],
            'source' => ['nullable', 'string', 'in:ios,android,web,api'],
            'metadata' => ['nullable', 'array'],
            'correlation_id' => ['nullable', 'uuid']
        ];
    }

    /**
     * Понятные сообщения об ошибках (по канону 2026).
     */
    public function messages(): array
    {
        return [
            'pickup_address.required' => 'Адрес подачи обязателен.',
            'pickup_lat.required' => 'Координаты подачи отсутствуют.',
            'dropoff_address.required' => 'Адрес назначения обязателен.',
            'estimated_distance.min' => 'Расстояние поездки слишком короткое.',
            'estimated_distance.max' => 'Расстояние превышает допустимый лимит (1000 км).'
        ];
    }

    /**
     * Настройка корректного ответа при ошибке валидации.
     */
    protected function failedValidation(Validator $validator): void
    {
        $response = response()->json([
            'status' => 'error',
            'message' => 'Ошибка валидации данных для поездки.',
            'errors' => $validator->errors(),
            'correlation_id' => $this->header('X-Correlation-Id')
        ], 422);

        throw new HttpResponseException($response);
    }
}

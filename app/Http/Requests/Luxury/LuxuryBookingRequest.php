<?php declare(strict_types=1);

namespace App\Http\Requests\Luxury;


use Illuminate\Contracts\Auth\Guard;
use Illuminate\Foundation\Http\FormRequest;

final class LuxuryBookingRequest extends FormRequest
{
    /**
         * Авторизация пользователя + Fraud Check
         */
        public function authorize(): bool
        {
            if (!$this->guard->check()) {
                return false;
            }

            // Fraud check на этапе авторизации для VIP секции
            $fraudService = app(FraudControlService::class);
            $correlationId = $this->header('X-Correlation-ID', (string) \Illuminate\Support\Str::uuid());

            try {
                $fraudService->check([
                    'user_id' => $this->guard->id(),
                    'ip' => $this->ip(),
                    'operation' => 'luxury_booking_access',
                    'correlation_id' => $correlationId,
                ]);
            } catch (\Throwable $e) {
                return false;
            }

            return true;
        }

        /**
         * Правила валидации
         */
        public function rules(): array
        {
            return [
                'client_uuid' => 'required|uuid|exists:luxury_clients,uuid',
                'bookable_type' => [
                    'required',
                    'string',
                    Rule::in(['product', 'service']),
                ],
                'bookable_uuid' => 'required|uuid',
                'booking_at' => [
                    'required',
                    'date',
                    'after:now',
                ],
                'duration_minutes' => 'nullable|integer|min:30',
                'notes' => 'nullable|string|max:1000',
                'correlation_id' => 'required|uuid',
            ];
        }

        /**
         * Сообщения об ошибках
         */
        public function messages(): array
        {
            return [
                'client_uuid.exists' => 'Указанный VIP-клиент не найден в системе.',
                'bookable_uuid.exists' => 'Объект бронирования не найден.',
                'booking_at.after' => 'Время бронирования должно быть в будущем.',
            ];
        }
}

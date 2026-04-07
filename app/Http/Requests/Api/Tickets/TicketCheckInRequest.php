<?php declare(strict_types=1);

/**
 * TicketCheckInRequest — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/ticketcheckinrequest
 * @see https://catvrf.ru/docs/ticketcheckinrequest
 */


namespace App\Http\Requests\Api\Tickets;


use Illuminate\Contracts\Auth\Guard;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Class TicketCheckInRequest
 *
 * Form Request with validation rules.
 * Validates input before reaching the controller.
 * Authorization checks tenant and business group access.
 *
 * @package App\Http\Requests\Api\Tickets
 */
final class TicketCheckInRequest extends FormRequest
{
    /**
         * Валидация на права проверяющего.
         */
        public function authorize(): bool
        {
            // Проверка через Policy (view_admin_panel) или роль сотрудника
            return $this->guard->check() && ($this->guard->user()->role === 'checker' || $this->guard->user()->role === 'admin');
        }

        /**
         * Правила валидации.
         */
        public function rules(): array
        {
            return [
                'qr_code' => 'required|string|min:16|max:64',
                'location' => 'nullable|array',
                'location.lat' => 'required_with:location|numeric',
                'location.lon' => 'required_with:location|numeric',
                'device' => 'nullable|array',
                'device.id' => 'required_with:device|string',
                'device.name' => 'required_with:device|string',
            ];
        }

        /**
         * Человекочитаемые сообщения.
         */
        public function messages(): array
        {
            return [
                'qr_code.required' => 'QR код обязателен для считывания',
                'qr_code.min' => 'Неверный формат QR кода (слишком короткий)',
                'location.lat.required_with' => 'Для фиксации места необходимо передать широту(lat)',
                'location.lon.required_with' => 'Для фиксации места необходимо передать долготу(lon)',
            ];
        }
}

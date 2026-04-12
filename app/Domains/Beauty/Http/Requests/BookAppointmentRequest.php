<?php declare(strict_types=1);

namespace App\Domains\Beauty\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * BookAppointmentRequest — валидация записи к мастеру салона.
 * Канон CatVRF 2026 — PRODUCTION MANDATORY.
 *
 * Проверяет: мастер, услуга, слот, клиент.
 * Резерв слота — 20 минут (канон корзины).
 */
final class BookAppointmentRequest extends FormRequest
{
    /**
     * Определяет, авторизован ли пользователь для этого запроса.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Правила валидации.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'salon_id'           => ['required', 'integer', 'exists:beauty_salons,id'],
            'master_id'          => ['required', 'integer', 'exists:beauty_masters,id'],
            'service_id'         => ['required', 'integer', 'exists:beauty_services,id'],
            'appointment_date'   => ['required', 'date', 'after:now'],
            'time_slot'          => ['required', 'string', 'regex:/^\d{2}:\d{2}$/'],
            'client_name'        => ['nullable', 'string', 'max:255'],
            'client_phone'       => ['nullable', 'string', 'max:20'],
            'notes'              => ['nullable', 'string', 'max:2000'],
            'idempotency_key'    => ['nullable', 'string', 'max:64'],
        ];
    }

    /**
     * Русские названия полей.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'salon_id'         => 'салон',
            'master_id'        => 'мастер',
            'service_id'       => 'услуга',
            'appointment_date' => 'дата записи',
            'time_slot'        => 'время записи',
            'client_name'      => 'имя клиента',
            'client_phone'     => 'телефон клиента',
            'notes'            => 'примечания',
            'idempotency_key'  => 'ключ идемпотентности',
        ];
    }
}

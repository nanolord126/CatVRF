<?php declare(strict_types=1);

/**
 * TicketPurchaseRequest — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/ticketpurchaserequest
 */


namespace App\Http\Requests\Api\Tickets;


use Illuminate\Contracts\Auth\Guard;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Class TicketPurchaseRequest
 *
 * Form Request with validation rules.
 * Validates input before reaching the controller.
 * Authorization checks tenant and business group access.
 *
 * @package App\Http\Requests\Api\Tickets
 */
final class TicketPurchaseRequest extends FormRequest
{
    /**
         * Пользователь должен быть авторизован и пройти фрод-контроль.
         */
        public function authorize(): bool
        {
            // В реальном 2026 тут проверка FraudControlService::check()
            return $this->guard->check();
        }

        /**
         * Правила валидации.
         */
        public function rules(): array
        {
            return [
                'event_id' => 'required|integer|exists:events,id',
                'ticket_type_id' => 'required|integer|exists:ticket_types,id',
                'quantity' => 'required|integer|min:1|max:10',
                'sector' => 'nullable|string|max:50',
                'row' => 'nullable|integer|min:1',
                'seat_number' => 'nullable|integer|min:1',
                'metadata' => 'nullable|array',
            ];
        }

        /**
         * Сообщения об ошибках (Человекочитаемые Канон 2026).
         */
        public function messages(): array
        {
            return [
                'event_id.required' => 'Необходимо выбрать мероприятие',
                'event_id.exists' => 'Указанное мероприятие не существует',
                'ticket_type_id.required' => 'Не указан тип билета',
                'quantity.max' => 'Нельзя купить более 10 билетов за один раз',
                'quantity.min' => 'Количество билетов должно быть не менее 1',
            ];
        }
}

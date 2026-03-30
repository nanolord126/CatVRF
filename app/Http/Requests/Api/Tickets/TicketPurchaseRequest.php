<?php declare(strict_types=1);

namespace App\Http\Requests\Api\Tickets;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class TicketPurchaseRequest extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    /**
         * Пользователь должен быть авторизован и пройти фрод-контроль.
         */
        public function authorize(): bool
        {
            // В реальном 2026 тут проверка FraudControlService::check()
            return auth()->check();
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

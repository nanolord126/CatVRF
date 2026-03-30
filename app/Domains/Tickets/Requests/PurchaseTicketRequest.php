<?php declare(strict_types=1);

namespace App\Domains\Tickets\Requests;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class PurchaseTicketRequest extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function authorize(): bool
        {
            // Канон: Fraud check перед мутацией
            return true;
        }

        public function rules(): array
        {
            return [
                'event_id' => ['required', 'exists:tickets_events,id'],
                'quantity' => ['required', 'integer', 'min:1', 'max:10'],
            ];
        }

        public function messages(): array
        {
            return [
                'event_id.exists' => 'Событие не найдено',
                'quantity.max' => 'Нельзя купить более 10 билетов за раз',
            ];
        }
}

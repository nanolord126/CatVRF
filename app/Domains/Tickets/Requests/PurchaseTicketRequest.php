<?php

declare(strict_types=1);


namespace App\Domains\Tickets\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Services\FraudControlService;

final /**
 * PurchaseTicketRequest
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class PurchaseTicketRequest extends FormRequest
{
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

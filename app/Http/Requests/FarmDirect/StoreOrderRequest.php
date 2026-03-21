<?php
declare(strict_types=1);

namespace App\Http\Requests\FarmDirect;

use Illuminate\Foundation\Http\FormRequest;

final class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        // CANON 2026: Fraud Check in FormRequest
        if (class_exists(\App\Services\Fraud\FraudControlService::class) && auth()->check()) {
            $fraudScore = app(\App\Services\Fraud\FraudControlService::class)->scoreOperation(new \stdClass());
            if ($fraudScore > 0.7 && !auth()->user()->hasRole('admin')) {
                \Illuminate\Support\Facades\Log::channel('audit')->warning('Fraud check blocked request', ['class' => __CLASS__, 'score' => $fraudScore]);
                return false;
            }
        }
        return true; // Tenant scoping handled in service
    }

    public function rules(): array
    {
        return [
            'product_id' => ['required', 'integer', 'exists:farm_products,id'],
            'quantity_kg' => ['required', 'numeric', 'min:0.5', 'max:500'],
            'delivery_date' => ['required', 'date', 'after:today'],
            'delivery_address' => ['required', 'string', 'max:500'],
            'phone' => ['required', 'string', 'regex:/^\\+?[0-9]{10,15}$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'quantity_kg.min' => 'Минимальный вес - 0,5 кг',
            'delivery_date.after' => 'Дата доставки должна быть в будущем',
            'phone.regex' => 'Некорректный номер телефона',
        ];
    }
}

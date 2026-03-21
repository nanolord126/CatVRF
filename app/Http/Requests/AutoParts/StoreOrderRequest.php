<?php
declare(strict_types=1);

namespace App\Http\Requests\AutoParts;

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
        return true;
    }

    public function rules(): array
    {
        return [
            'part_id' => ['required', 'integer', 'exists:auto_part_items,id'],
            'client_id' => ['required', 'integer', 'exists:users,id'],
            'vin' => ['required', 'string', 'regex:/^[A-HJ-NPR-Z0-9]{17}$/'],
            'quantity' => ['required', 'integer', 'min:1', 'max:100'],
            'delivery_date' => ['required', 'date', 'after:today'],
        ];
    }

    public function messages(): array
    {
        return [
            'vin.regex' => 'Invalid VIN format (17 characters, no I, O, Q)',
        ];
    }
}

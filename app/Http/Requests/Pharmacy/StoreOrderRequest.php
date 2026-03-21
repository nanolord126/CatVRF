<?php
declare(strict_types=1);

namespace App\Http\Requests\Pharmacy;

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
            'prescription_id' => ['sometimes', 'integer', 'exists:prescriptions,id'],
            'client_id' => ['required', 'integer', 'exists:users,id'],
            'medicines' => ['required', 'json'],
            'delivery_date' => ['required', 'date', 'after:today'],
        ];
    }
}

<?php
declare(strict_types=1);

namespace App\Http\Requests\FarmDirect;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateOrderRequest extends FormRequest
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
            'delivery_date' => ['sometimes', 'date', 'after:today'],
            'delivery_address' => ['sometimes', 'string', 'max:500'],
            'phone' => ['sometimes', 'string', 'regex:/^\\+?[0-9]{10,15}$/'],
        ];
    }
}

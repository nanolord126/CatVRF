<?php
declare(strict_types=1);

namespace App\Http\Requests\OfficeCatering;

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
            'client_id' => ['required', 'integer', 'exists:corporate_clients,id'],
            'menu_id' => ['required', 'integer', 'exists:office_menus,id'],
            'portions' => ['required', 'integer', 'min:1', 'max:500'],
            'delivery_date' => ['required', 'date', 'after:today'],
            'delivery_time' => ['required', 'date_format:H:i', 'between:08:00,17:00'],
        ];
    }
}

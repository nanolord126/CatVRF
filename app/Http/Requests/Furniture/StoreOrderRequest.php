<?php
declare(strict_types=1);

namespace App\Http\Requests\Furniture;

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
            'item_id' => ['required', 'integer', 'exists:furniture_items,id'],
            'client_id' => ['required', 'integer', 'exists:users,id'],
            'client_address' => ['required', 'string', 'max:500'],
            'delivery_date' => ['required', 'date', 'after:today'],
            'needs_assembly' => ['sometimes', 'boolean'],
            'assembly_date' => ['sometimes', 'date', 'after:delivery_date'],
        ];
    }
}

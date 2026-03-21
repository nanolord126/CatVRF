<?php
declare(strict_types=1);

namespace App\Http\Requests\MeatShops;

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
            'product_id' => ['required', 'integer', 'exists:meat_shop_products,id'],
            'client_id' => ['required', 'integer', 'exists:users,id'],
            'weight_kg' => ['required', 'numeric', 'min:0.2', 'max:50'],
            'delivery_date' => ['required', 'date', 'after:today'],
        ];
    }
}

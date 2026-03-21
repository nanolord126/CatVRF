<?php
declare(strict_types=1);

namespace App\Http\Requests\HealthyFood;

use Illuminate\Foundation\Http\FormRequest;

final class StoreDietPlanRequest extends FormRequest
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
            'client_id' => ['required', 'integer', 'exists:users,id'],
            'diet_type' => ['required', 'string', 'in:keto,vegan,paleo,low-carb,balanced,custom'],
            'duration_days' => ['required', 'integer', 'min:7', 'max:365'],
            'daily_calories' => ['required', 'integer', 'min:1000', 'max:5000'],
            'preferences' => ['sometimes', 'json'],
        ];
    }
}

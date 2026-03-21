<?php
declare(strict_types=1);

namespace App\Http\Requests;

final class ReferralClaimRequest extends BaseApiRequest
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
        return auth()->check();
    }
    
    public function rules(): array
    {
        return [
            'referral_id' => ['required', 'integer', 'exists:referrals,id'],
            'confirm_turnover' => ['required', 'boolean'],  // Подтвердить, что оборот достигнут
        ];
    }
    
    public function messages(): array
    {
        return [
            'referral_id.required' => 'Referral ID is required',
            'referral_id.integer' => 'Referral ID must be an integer',
            'referral_id.exists' => 'Referral not found',
            'confirm_turnover.required' => 'Turnover confirmation is required',
            'confirm_turnover.boolean' => 'Turnover confirmation must be true or false',
        ];
    }
}

declare(strict_types=1);

<?php

declare(strict_types=1);

namespace App\Domains\Finances\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final /**
 * UpdatePaymentTransactionRequest
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class UpdatePaymentTransactionRequest extends FormRequest
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
            'status' => 'nullable|string|in:pending,processing,completed,failed,refunded',
            'description' => 'nullable|string|max:500',
        ];
    }
}

<?php declare(strict_types=1);

namespace Modules\Finances\Http\Requests;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class StorePaymentTransactionRequest extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
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
                'user_id' => 'required|uuid',
                'amount' => 'required|numeric|min:0.01|max:999999.99',
                'description' => 'nullable|string|max:500',
                'reference_id' => 'nullable|string|unique:payment_transactions',
                'status' => 'nullable|string|in:pending,processing,completed,failed,refunded',
                'payment_method' => 'nullable|string|max:100',
            ];
        }
}

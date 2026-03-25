declare(strict_types=1);

<?php

declare(strict_types=1);

namespace App\Domains\Common\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final /**
 * UpdateCommonResourceRequest
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class UpdateCommonResourceRequest extends FormRequest
{
    public function authorize(): bool
    {
        // CANON 2026: Fraud Check in FormRequest
        if (class_exists(\App\Services\Fraud\FraudControlService::class) && auth()->check()) {
            $fraudScore = app(\App\Services\Fraud\FraudControlService::class)->scoreOperation(new \stdClass());
            if ($fraudScore > 0.7 && !auth()->user()->hasRole('admin')) {
                \Illuminate\Support\Facades\$this->log->channel('audit')->warning('Fraud check blocked request', ['class' => __CLASS__, 'score' => $fraudScore]);
                return false;
            }
        }
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'nullable|string|max:255',
            'status' => 'nullable|string|in:active,inactive',
            'data' => 'nullable|json',
        ];
    }
}

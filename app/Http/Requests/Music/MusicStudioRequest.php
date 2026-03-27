<?php

declare(strict_types=1);

namespace App\Http\Requests\Music;

use App\Services\Security\FraudControlService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * MusicStudioRequest validates studio creation and updates.
 * Follows 2026 security and architectural canon.
 */
final class MusicStudioRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Fraud check for studio management
        $fraudCheck = FraudControlService::check([
            'user_id' => auth()->id(),
            'ip' => $this->ip(),
            'action' => 'studio_mutation',
            'tenant_id' => tenant()->id,
        ]);

        if (!$fraudCheck->isAllowed()) {
            Log::channel('fraud_alert')->warning('Blocked music studio mutation attempt', [
                'user_id' => auth()->id(),
                'ip' => $this->ip(),
                'reason' => $fraudCheck->reason(),
            ]);
            return false;
        }

        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'store_id' => 'required|integer|exists:music_stores,id',
            'hourly_rate' => 'required|integer|min:0',
            'capacity' => 'required|integer|min:1',
            'address' => 'required|string|max:255',
            'description' => 'nullable|string',
            'equipment_list' => 'nullable|array',
            'is_rehearsal_room' => 'boolean',
            'is_recording_studio' => 'boolean',
            'is_active' => 'boolean',
            'tags' => 'nullable|array',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Введите название студии.',
            'store_id.required' => 'Укажите магазин, которому принадлежит студия.',
            'hourly_rate.required' => 'Укажите почасовую ставку (в копейках).',
            'capacity.required' => 'Укажите вместимость студии (человек).',
            'address.required' => 'Введите адрес студии.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        if (empty($this->correlation_id)) {
            $this->merge([
                'correlation_id' => (string) Str::uuid(),
            ]);
        }
    }
}

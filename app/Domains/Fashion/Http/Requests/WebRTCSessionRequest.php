<?php declare(strict_types=1);

namespace App\Domains\Fashion\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class WebRTCSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'stylist_id' => ['nullable', 'integer', 'exists:fashion_stylists,id'],
            'scheduled_time' => ['nullable', 'date', 'after:now'],
            'is_b2b' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'stylist_id.exists' => 'Stylist not found',
            'scheduled_time.after' => 'Scheduled time must be in the future',
        ];
    }
}

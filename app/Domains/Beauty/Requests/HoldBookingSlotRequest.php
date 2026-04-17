<?php declare(strict_types=1);

namespace App\Domains\Beauty\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

final class HoldBookingSlotRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'booking_slot_id' => ['required', 'integer', 'exists:beauty_booking_slots,id'],
            'customer_id' => ['required', 'integer', 'exists:users,id'],
            'tenant_id' => ['required', 'integer', 'exists:tenants,id'],
            'business_group_id' => ['nullable', 'integer', 'exists:business_groups,id'],
            'is_b2b' => ['boolean'],
            'idempotency_key' => ['nullable', 'string', 'max:64'],
        ];
    }

    public function prepareForValidation(): void
    {
        $this->merge([
            'is_b2b' => $this->has('inn') && $this->has('business_card_id'),
        ]);
    }

    public function getCorrelationId(): string
    {
        return $this->header('X-Correlation-ID') ?? Str::uuid()->toString();
    }

    public function getIdempotencyKey(): ?string
    {
        return $this->input('idempotency_key') ?? $this->header('X-Idempotency-Key');
    }
}

<?php declare(strict_types=1);

namespace App\Domains\Beauty\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

final class ReleaseBookingSlotRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'booking_slot_id' => ['required', 'integer', 'exists:beauty_booking_slots,id'],
            'tenant_id' => ['required', 'integer', 'exists:tenants,id'],
            'reason' => ['nullable', 'string', 'in:payment_failed,expired,cancelled,manual'],
        ];
    }

    public function getCorrelationId(): string
    {
        return $this->header('X-Correlation-ID') ?? Str::uuid()->toString();
    }
}

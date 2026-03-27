<?php declare(strict_types=1);

namespace App\Domains\Beauty\Http\Requests;

use App\Services\FraudControlService;
use Illuminate\Foundation\Http\FormRequest;

/**
 * КАНОН 2026: Beauty Salon Request
 */
final class CreateBeautySalonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return FraudControlService::check(
            userId: auth()->id() ?? 0,
            operationType: 'beauty_salon_create',
            amount: 0
        );
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string', 'max:500'],
            'description' => ['nullable', 'string'],
            'phone' => ['required', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'schedule' => ['nullable', 'array'],
            'tags' => ['nullable', 'array'],
            'is_active' => ['boolean'],
        ];
    }
}

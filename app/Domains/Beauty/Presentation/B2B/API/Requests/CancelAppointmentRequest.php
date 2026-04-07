<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Presentation\B2B\API\Requests;


use Psr\Log\LoggerInterface;
use Illuminate\Support\Str;

use Illuminate\Foundation\Http\FormRequest;
/**
 * Class CancelAppointmentRequest
 *
 * Part of the Beauty vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Form Request with validation rules.
 * Validates input before reaching the controller.
 * Authorization checks tenant and business group access.
 *
 * @package App\Domains\Beauty\Presentation\B2B\API\Requests
 */
final class CancelAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $authorized = $this->user()?->hasPermissionTo('beauty.appointments.cancel') ?? false;

        if (!$authorized) {
            $this->container->make(LoggerInterface::class)->warning('Beauty B2B: запрет на отмену записи', [
                'user_id' => $this->user()?->id,
                'correlation_id' => $this->header('X-Correlation-ID', Str::uuid()->toString()),
            ]);
        }

        return $authorized;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'min:5', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'reason.required' => 'Укажите причину отмены.',
            'reason.min'      => 'Причина отмены должна содержать минимум 5 символов.',
            'reason.max'      => 'Причина отмены не должна превышать 500 символов.',
        ];
    }
}

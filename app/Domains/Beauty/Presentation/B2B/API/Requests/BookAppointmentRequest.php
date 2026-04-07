<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Presentation\B2B\API\Requests;


use Psr\Log\LoggerInterface;
use Illuminate\Support\Str;

use Illuminate\Foundation\Http\FormRequest;
final class BookAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $authorized = $this->user()?->hasPermissionTo('beauty.appointments.create') ?? false;

        if (!$authorized) {
            $this->container->make(LoggerInterface::class)->warning('Beauty B2B: запрет на создание записи', [
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
            'salon_uuid'   => ['required', 'string', 'uuid'],
            'master_uuid'  => ['required', 'string', 'uuid'],
            'service_uuid' => ['required', 'string', 'uuid'],
            'client_id'    => ['required', 'integer', 'min:1'],
            'start_at'     => ['required', 'date', 'after:now'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'salon_uuid.required'   => 'Укажите UUID салона.',
            'salon_uuid.uuid'       => 'UUID салона должен быть валидным UUID.',
            'master_uuid.required'  => 'Укажите UUID мастера.',
            'master_uuid.uuid'      => 'UUID мастера должен быть валидным UUID.',
            'service_uuid.required' => 'Укажите UUID услуги.',
            'service_uuid.uuid'     => 'UUID услуги должен быть валидным UUID.',
            'client_id.required'    => 'Укажите ID клиента.',
            'client_id.integer'     => 'ID клиента должен быть числом.',
            'start_at.required'     => 'Укажите дату и время записи.',
            'start_at.date'         => 'Дата записи должна быть корректной датой.',
            'start_at.after'        => 'Дата записи должна быть в будущем.',
        ];
    }
}

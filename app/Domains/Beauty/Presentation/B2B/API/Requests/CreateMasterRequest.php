<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Presentation\B2B\API\Requests;


use Psr\Log\LoggerInterface;
use Illuminate\Support\Str;

use Illuminate\Foundation\Http\FormRequest;
final class CreateMasterRequest extends FormRequest
{
    public function authorize(): bool
    {
        $authorized = $this->user()?->hasPermissionTo('beauty.masters.create') ?? false;

        if (!$authorized) {
            $this->container->make(LoggerInterface::class)->warning('Beauty B2B: запрет на создание мастера', [
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
            'salon_uuid'       => ['required', 'string', 'uuid'],
            'name'             => ['required', 'string', 'min:2', 'max:150'],
            'specialization'   => ['required', 'string', 'min:2', 'max:100'],
            'experience_years' => ['sometimes', 'integer', 'min:0', 'max:60'],
            'work_days'        => ['sometimes', 'array', 'min:1', 'max:7'],
            'work_days.*'      => ['string', 'in:mon,tue,wed,thu,fri,sat,sun'],
            'work_start'       => ['sometimes', 'date_format:H:i'],
            'work_end'         => ['sometimes', 'date_format:H:i', 'after:work_start'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'salon_uuid.required'      => 'Укажите UUID салона.',
            'salon_uuid.uuid'          => 'UUID салона должен быть валидным UUID.',
            'name.required'            => 'Укажите имя мастера.',
            'name.min'                 => 'Имя мастера должно содержать минимум 2 символа.',
            'name.max'                 => 'Имя мастера не должно превышать 150 символов.',
            'specialization.required'  => 'Укажите специализацию мастера.',
            'experience_years.integer' => 'Стаж должен быть числом.',
            'experience_years.min'     => 'Стаж не может быть отрицательным.',
            'work_days.array'          => 'Рабочие дни должны быть массивом.',
            'work_days.*.in'           => 'Рабочий день должен быть одним из: mon, tue, wed, thu, fri, sat, sun.',
            'work_start.date_format'   => 'Время начала работы должно быть в формате ЧЧ:ММ.',
            'work_end.date_format'     => 'Время окончания работы должно быть в формате ЧЧ:ММ.',
            'work_end.after'           => 'Время окончания должно быть позже времени начала.',
        ];
    }
}

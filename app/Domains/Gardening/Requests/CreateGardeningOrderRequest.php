<?php declare(strict_types=1);

namespace App\Domains\Gardening\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CreateGardeningOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'business_group_id' => ['nullable', 'integer', 'min:1'],
            'service_type'      => ['required', 'string', 'in:landscaping,mowing,planting,pruning,irrigation,design'],
            'address'           => ['required', 'string', 'min:5', 'max:512'],
            'area_sqm'          => ['required', 'numeric', 'min:1'],
            'scheduled_at'      => ['required', 'date', 'after:now'],
            'is_recurring'      => ['sometimes', 'boolean'],
            'recurrence_type'   => ['required_if:is_recurring,true', 'string', 'in:weekly,biweekly,monthly'],
            'notes'             => ['sometimes', 'string', 'max:1000'],
            'tags'              => ['sometimes', 'array'],
            'tags.*'            => ['string', 'max:64'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'service_type.required'    => 'Тип услуги обязателен.',
            'address.required'         => 'Адрес обязателен.',
            'area_sqm.required'        => 'Площадь участка обязательна.',
            'scheduled_at.required'    => 'Дата и время услуги обязательны.',
            'recurrence_type.required_if' => 'Периодичность обязательна для повторяющихся услуг.',
        ];
    }
}

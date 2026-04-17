<?php declare(strict_types=1);

namespace App\Domains\Inventory\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CreateInventoryCheckRequest extends FormRequest
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
            'warehouse_id'      => ['required', 'integer', 'min:1'],
            'type'              => ['required', 'string', 'in:full,partial,spot'],
            'scheduled_at'      => ['required', 'date', 'after_or_equal:today'],
            'employee_ids'      => ['required', 'array', 'min:1'],
            'employee_ids.*'    => ['integer', 'min:1'],
            'categories'        => ['sometimes', 'array'],
            'categories.*'      => ['string', 'max:128'],
            'notes'             => ['sometimes', 'string', 'max:1000'],
            'tags'              => ['sometimes', 'array'],
            'tags.*'            => ['string', 'max:64'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'warehouse_id.required'  => 'Склад обязателен.',
            'type.required'          => 'Тип инвентаризации обязателен.',
            'scheduled_at.required'  => 'Дата инвентаризации обязательна.',
            'employee_ids.required'  => 'Исполнители обязательны.',
        ];
    }
}

<?php declare(strict_types=1);

namespace App\Domains\Staff\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CreateStaffMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'business_group_id'  => ['nullable', 'integer', 'min:1'],
            'user_id'            => ['sometimes', 'integer', 'min:1'],
            'full_name'          => ['required', 'string', 'min:2', 'max:255'],
            'position'           => ['required', 'string', 'max:128'],
            'employment_type'    => ['required', 'string', 'in:full_time,part_time,contract,freelance'],
            'phone'              => ['required', 'string', 'regex:/^\+?[0-9]{10,15}$/'],
            'email'              => ['required', 'email', 'max:255'],
            'base_salary'        => ['required', 'numeric', 'min:0'],
            'hire_date'          => ['required', 'date', 'before_or_equal:today'],
            'vertical'           => ['sometimes', 'string', 'max:64'],
            'permissions'        => ['sometimes', 'array'],
            'permissions.*'      => ['string', 'max:128'],
            'tags'               => ['sometimes', 'array'],
            'tags.*'             => ['string', 'max:64'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'full_name.required'      => 'ФИО сотрудника обязательно.',
            'position.required'       => 'Должность обязательна.',
            'employment_type.required' => 'Тип занятости обязателен.',
            'phone.required'          => 'Телефон обязателен.',
            'email.required'          => 'Email обязателен.',
            'base_salary.required'    => 'Оклад обязателен.',
            'hire_date.required'      => 'Дата найма обязательна.',
        ];
    }
}

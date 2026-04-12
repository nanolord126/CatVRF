<?php declare(strict_types=1);

namespace App\Domains\Beauty\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * CreateSalonRequest — валидация создания салона красоты.
 * Канон CatVRF 2026 — PRODUCTION MANDATORY.
 *
 * Все поля обязательно tenant-scoped + correlation_id.
 * B2B-клиенты дополнительно указывают business_group_id.
 */
final class CreateSalonRequest extends FormRequest
{
    /**
     * Определяет, авторизован ли пользователь для этого запроса.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Правила валидации.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'name'              => ['required', 'string', 'min:2', 'max:255'],
            'address'           => ['required', 'string', 'max:500'],
            'lat'               => ['required', 'numeric', 'between:-90,90'],
            'lon'               => ['required', 'numeric', 'between:-180,180'],
            'phone'             => ['required', 'string', 'max:20'],
            'email'             => ['nullable', 'email', 'max:255'],
            'description'       => ['nullable', 'string', 'max:5000'],
            'working_hours'     => ['nullable', 'array'],
            'working_hours.*'   => ['string'],
            'tags'              => ['nullable', 'array'],
            'tags.*'            => ['string', 'max:64'],
            'business_group_id' => ['nullable', 'integer', 'exists:business_groups,id'],
        ];
    }

    /**
     * Русские названия полей.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name'              => 'название салона',
            'address'           => 'адрес',
            'lat'               => 'широта',
            'lon'               => 'долгота',
            'phone'             => 'телефон',
            'email'             => 'email',
            'description'       => 'описание',
            'working_hours'     => 'часы работы',
            'tags'              => 'теги',
            'business_group_id' => 'филиал',
        ];
    }
}

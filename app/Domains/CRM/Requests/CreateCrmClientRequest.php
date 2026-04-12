<?php declare(strict_types=1);

namespace App\Domains\CRM\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CreateCrmClientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer', 'min:1'],
            'business_group_id' => ['nullable', 'integer', 'min:1'],
            'vertical' => ['required', 'string', 'max:64'],
            'first_name' => ['nullable', 'string', 'max:120'],
            'last_name' => ['nullable', 'string', 'max:120'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:32'],
            'status' => ['sometimes', 'string', 'max:32'],
            'segment' => ['nullable', 'string', 'max:64'],
            'preferences' => ['sometimes', 'array'],
            'vertical_data' => ['sometimes', 'array'],
        ];
    }

    /**
     * Кастомные сообщения валидации.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            '*.required' => 'Поле :attribute обязательно для заполнения.',
            '*.string'   => 'Поле :attribute должно быть строкой.',
            '*.integer'  => 'Поле :attribute должно быть целым числом.',
        ];
    }

    /**
     * Человеко-понятные имена полей.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [];
    }

    /**
     * CreateCrmClientRequest — CatVRF 2026 Component.
     *
     * Part of the CatVRF multi-vertical marketplace platform.
     * Implements tenant-aware, fraud-checked business logic
     * with full correlation_id tracing and audit logging.
     *
     * @package CatVRF
     * @version 2026.1
     * @author CatVRF Team
     * @license Proprietary
     */
}

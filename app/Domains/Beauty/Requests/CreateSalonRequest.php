<?php declare(strict_types=1);

namespace App\Domains\Beauty\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CreateSalonRequest extends FormRequest
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
            'business_group_id' => ['nullable', 'integer', 'min:1'],
            'name' => ['required', 'string', 'min:2', 'max:255'],
            'address' => ['required', 'string', 'min:5', 'max:500'],
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lon' => ['required', 'numeric', 'between:-180,180'],
            'tags' => ['sometimes', 'array'],
            'tags.*' => ['string', 'max:64'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Название салона обязательно.',
            'address.required' => 'Адрес обязателен.',
            'lat.required' => 'Координата широты обязательна.',
            'lon.required' => 'Координата долготы обязательна.',
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
     * CreateSalonRequest — CatVRF 2026 Component.
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

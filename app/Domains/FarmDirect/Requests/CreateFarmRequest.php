<?php declare(strict_types=1);

namespace App\Domains\FarmDirect\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CreateFarmRequest extends FormRequest
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
            'name'              => ['required', 'string', 'min:2', 'max:255'],
            'address'           => ['required', 'string', 'min:5', 'max:512'],
            'lat'               => ['required', 'numeric', 'between:-90,90'],
            'lon'               => ['required', 'numeric', 'between:-180,180'],
            'region'            => ['required', 'string', 'max:128'],
            'certifications'    => ['sometimes', 'array'],
            'certifications.*'  => ['string', 'in:organic,eco,bio,gmo_free,fair_trade'],
            'description'       => ['sometimes', 'string', 'max:4000'],
            'delivery_radius_km' => ['sometimes', 'numeric', 'min:1', 'max:1000'],
            'is_active'         => ['sometimes', 'boolean'],
            'tags'              => ['sometimes', 'array'],
            'tags.*'            => ['string', 'max:64'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'name.required'    => 'Название фермы обязательно.',
            'address.required' => 'Адрес обязателен.',
            'region.required'  => 'Регион обязателен.',
        ];
    }
}

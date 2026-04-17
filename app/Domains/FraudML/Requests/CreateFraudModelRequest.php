<?php declare(strict_types=1);

namespace App\Domains\FraudML\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CreateFraudModelRequest extends FormRequest
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
            'version'           => ['required', 'string', 'regex:/^\d{4}-\d{2}-\d{2}-v\d+$/'],
            'algorithm'         => ['required', 'string', 'in:xgboost,lightgbm,random_forest,neural_network'],
            'threshold_block'   => ['required', 'numeric', 'between:0,1'],
            'threshold_review'  => ['required', 'numeric', 'between:0,1', 'lt:threshold_block'],
            'features'          => ['required', 'array', 'min:1'],
            'features.*'        => ['string', 'max:128'],
            'tags'              => ['sometimes', 'array'],
            'tags.*'            => ['string', 'max:64'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'version.regex'          => 'Формат версии: YYYY-MM-DD-vN.',
            'threshold_review.lt'    => 'Порог review должен быть ниже порога блокировки.',
        ];
    }
}

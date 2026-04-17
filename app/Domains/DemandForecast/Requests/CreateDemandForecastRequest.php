<?php declare(strict_types=1);

namespace App\Domains\DemandForecast\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CreateDemandForecastRequest extends FormRequest
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
            'vertical'          => ['required', 'string', 'max:64'],
            'product_id'        => ['required', 'integer', 'min:1'],
            'warehouse_id'      => ['sometimes', 'integer', 'min:1'],
            'forecast_period'   => ['required', 'string', 'in:7d,14d,30d,90d'],
            'algorithm'         => ['sometimes', 'string', 'in:linear,xgboost,arima,prophet'],
            'tags'              => ['sometimes', 'array'],
            'tags.*'            => ['string', 'max:64'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'vertical.required'        => 'Вертикаль обязательна.',
            'product_id.required'      => 'Товар обязателен.',
            'forecast_period.required' => 'Период прогноза обязателен.',
        ];
    }
}

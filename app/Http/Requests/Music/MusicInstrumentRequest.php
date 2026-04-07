<?php declare(strict_types=1);

namespace App\Http\Requests\Music;



use Illuminate\Http\Request;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Foundation\Http\FormRequest;

final class MusicInstrumentRequest extends FormRequest
{
    public function __construct(
        private readonly Request $request,
    ) {}

    /**
         * Determine if the user is authorized to make this request.
         */
        public function authorize(): bool
        {
            // Fraud control check before any mutation
            app(\App\Services\FraudControlService::class)->check(
                userId: (int) $this->guard->id(),
                operationType: 'instrument_mutation',
                amount: 0,
                correlationId: $this->request->header('X-Correlation-ID', \Illuminate\Support\Str::uuid()->toString()),
            );
return $this->guard->check();
        }

        /**
         * Get the validation rules that apply to the request.
         */
        public function rules(): array
        {
            return [
                'name' => 'required|string|max:255',
                'store_id' => 'required|integer|exists:music_stores,id',
                'brand' => 'required|string|max:255',
                'model' => 'nullable|string|max:255',
                'category_id' => 'required|integer',
                'description' => 'nullable|string',
                'price' => 'required|integer|min:0',
                'rental_price_per_day' => 'nullable|integer|min:0',
                'current_stock' => 'required|integer|min:0',
                'min_stock_threshold' => 'required|integer|min:0',
                'condition' => 'required|in:new,used,refurbished',
                'is_rentable' => 'boolean',
                'is_active' => 'boolean',
                'specifications' => 'nullable|array',
                'tags' => 'nullable|array',
            ];
        }

        /**
         * Get custom messages for validator errors.
         */
        public function messages(): array
        {
            return [
                'name.required' => 'Введите название инструмента.',
                'store_id.required' => 'Укажите магазин, которому принадлежит инструмент.',
                'price.required' => 'Укажите стоимость продажи.',
                'current_stock.required' => 'Укажите текущее количество на складе.',
                'condition.in' => 'Выберите корректное состояние инструмента: новое, б/у или восстановленное.',
            ];
        }

        /**
         * Prepare the data for validation.
         */
        protected function prepareForValidation(): void
        {
            if (empty($this->correlation_id)) {
                $this->merge([
                    'correlation_id' => (string) Str::uuid(),
                ]);
            }
        }
}

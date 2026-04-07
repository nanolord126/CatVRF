<?php declare(strict_types=1);

namespace App\Domains\Pharmacy\Http\Requests;


use Illuminate\Contracts\Auth\Guard;
final class PharmacyOrderStoreRequest
{
    public function __construct(
        private readonly Guard $guard) {}


    public function authorize(): bool
        {
            return $this->guard->check();
        }

        public function rules(): array
        {
            return [
                'pharmacy_id' => ['required', 'integer', 'exists:pharmacies,id'],
                'items' => ['required', 'array', 'min:1'],
                'items.*.medicine_id' => ['required', 'integer', 'exists:pharmacy_medicines,id'],
                'items.*.quantity' => ['required', 'integer', 'min:1', 'max:100'],
                'prescription_data' => ['nullable', 'string', 'max:5000'],
            ];
        }

        public function messages(): array
        {
            return [
                'pharmacy_id.required' => 'Выберите аптеку',
                'pharmacy_id.exists' => 'Аптека не найдена',
                'items.required' => 'Добавьте хотя бы один препарат',
                'items.*.medicine_id.required' => 'Укажите ID препарата',
                'items.*.quantity.required' => 'Укажите количество',
                'items.*.quantity.min' => 'Минимальное количество: 1',
            ];
        }

    /**
     * Get the string representation of this instance.
     *
     * @return string The string representation
     */
    public function __toString(): string
    {
        return static::class;
    }

    /**
     * Get debug information for this instance.
     *
     * @return array<string, mixed> Debug data including class name and state
     */
    public function toDebugArray(): array
    {
        return [
            'class' => static::class,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}

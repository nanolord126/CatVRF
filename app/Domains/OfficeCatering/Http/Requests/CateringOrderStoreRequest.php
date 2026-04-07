<?php declare(strict_types=1);

namespace App\Domains\OfficeCatering\Http\Requests;


use Illuminate\Contracts\Auth\Guard;
final class CateringOrderStoreRequest
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
                'catering_company_id' => 'required|integer|exists:catering_companies,id',
                'menu_id' => 'required|integer|exists:catering_menus,id',
                'office_name' => 'required|string|max:255',
                'office_address' => 'required|string|max:500',
                'person_count' => 'required|integer|min:5|max:500',
                'delivery_datetime' => 'required|date_format:Y-m-d H:i:s|after:now',
                'special_requests' => 'nullable|string|max:1000',
            ];
        }

        public function messages(): array
        {
            return [
                'catering_company_id.required' => 'Выберите кейтеринг',
                'menu_id.required' => 'Выберите меню',
                'office_name.required' => 'Укажите название офиса',
                'office_address.required' => 'Укажите адрес офиса',
                'person_count.required' => 'Укажите количество персон',
                'person_count.min' => 'Минимум 5 персон',
                'person_count.max' => 'Максимум 500 персон',
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

<?php declare(strict_types=1);

namespace App\Domains\Auto\Requests;

use App\Domains\Auto\DTOs\CarImportDto;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

final class CarImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'vin' => ['required', 'string', 'min:17', 'max:17', 'regex:/^[A-HJ-NPR-Z0-9]{17}$/'],
            'country' => ['required', 'string', 'min:2', 'max:2'],
            'declared_value' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'in:eur,usd,jpy,cny,krw'],
            'engine_type' => ['required', 'string', 'in:petrol,diesel,electric,hybrid'],
            'engine_volume' => ['nullable', 'numeric', 'min:0'],
            'manufacture_year' => ['required', 'integer', 'min:1900', 'max:' . (date('Y') + 1)],
            'documents.*' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'inn' => ['nullable', 'string', 'min:10', 'max:12'],
            'business_card_id' => ['nullable', 'string', 'min:1', 'max:50'],
        ];
    }

    public function messages(): array
    {
        return [
            'vin.required' => 'VIN код обязателен',
            'vin.regex' => 'VIN код должен содержать 17 символов (без I, O, Q)',
            'country.required' => 'Страна происхождения обязательна',
            'declared_value.required' => 'Объявленная стоимость обязательна',
            'currency.in' => 'Неверная валюта',
            'engine_type.in' => 'Неверный тип двигателя',
            'manufacture_year.max' => 'Год производства не может быть больше текущего года',
        ];
    }

    public function toDto(): CarImportDto
    {
        return CarImportDto::from($this);
    }
}

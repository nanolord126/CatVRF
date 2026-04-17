<?php declare(strict_types=1);

namespace App\Domains\Auto\Requests;

use App\Domains\Auto\DTOs\AIDiagnosticsDto;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

final class AIDiagnosticsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'vin' => ['required', 'string', 'min:17', 'max:17', 'regex:/^[A-HJ-NPR-Z0-9]{17}$/'],
            'photo' => ['required', 'file', 'image', 'mimes:jpeg,jpg,png,webp', 'max:10240'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'inn' => ['nullable', 'string', 'min:10', 'max:12'],
            'business_card_id' => ['nullable', 'string', 'min:1', 'max:50'],
        ];
    }

    public function messages(): array
    {
        return [
            'vin.required' => 'VIN код обязателен',
            'vin.regex' => 'VIN код должен содержать 17 символов (без I, O, Q)',
            'photo.required' => 'Фотография автомобиля обязательна',
            'photo.image' => 'Файл должен быть изображением',
            'photo.max' => 'Размер изображения не должен превышать 10MB',
            'latitude.between' => 'Широта должна быть между -90 и 90',
            'longitude.between' => 'Долгота должна быть между -180 и 180',
        ];
    }

    public function toDto(): AIDiagnosticsDto
    {
        $photo = $this->file('photo');
        if ($photo === null || !$photo instanceof UploadedFile) {
            throw new \RuntimeException('Valid photo file is required');
        }

        return AIDiagnosticsDto::from($this);
    }
}

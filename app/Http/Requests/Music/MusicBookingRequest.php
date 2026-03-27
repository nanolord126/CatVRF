<?php

declare(strict_types=1);

namespace App\Http\Requests\Music;

use Illuminate\Foundation\Http\FormRequest;
use App\Services\FraudControlService;

/**
 * MusicBookingRequest handles validation for music bookings (studios, lessons, rentals).
 */
class MusicBookingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        FraudControlService::check();
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'bookable_id' => ['required', 'integer'],
            'bookable_type' => ['required', 'string', 'in:App\Domains\MusicAndInstruments\Music\Models\MusicStudio,App\Domains\MusicAndInstruments\Music\Models\MusicLesson,App\Domains\MusicAndInstruments\Music\Models\MusicInstrument'],
            'starts_at' => ['required', 'date', 'after:now'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'total_price_cents' => ['required', 'integer', 'min:0'],
            'metadata' => ['nullable', 'array'],
            'tags' => ['nullable', 'array'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'bookable_id.required' => 'Выберите объект бронирования.',
            'starts_at.after' => 'Время начала бронирования должно быть в будущем.',
            'ends_at.after' => 'Время окончания бронирования должно быть после начала.',
            'total_price_cents.min' => 'Цена не может быть отрицательной.',
        ];
    }
}

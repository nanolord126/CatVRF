<?php declare(strict_types=1);

namespace App\Domains\Photography\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CreatePhotoSessionRequest extends FormRequest
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
            'photographer_id'   => ['required', 'integer', 'min:1'],
            'session_type'      => ['required', 'string', 'in:portrait,wedding,corporate,product,event,real_estate,aerial'],
            'location'          => ['required', 'string', 'max:512'],
            'duration_hours'    => ['required', 'numeric', 'min:0.5', 'max:24'],
            'scheduled_at'      => ['required', 'date', 'after:now'],
            'guests_count'      => ['sometimes', 'integer', 'min:1'],
            'description'       => ['sometimes', 'string', 'max:2000'],
            'retouching'        => ['sometimes', 'boolean'],
            'photos_count'      => ['required', 'integer', 'min:1', 'max:9999'],
            'tags'              => ['sometimes', 'array'],
            'tags.*'            => ['string', 'max:64'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'photographer_id.required' => 'Фотограф обязателен.',
            'session_type.required'    => 'Тип фотосессии обязателен.',
            'location.required'        => 'Место съёмки обязательно.',
            'duration_hours.required'  => 'Длительность обязательна.',
            'scheduled_at.required'    => 'Дата и время обязательны.',
            'photos_count.required'    => 'Количество фотографий обязательно.',
        ];
    }
}

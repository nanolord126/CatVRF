<?php

declare(strict_types=1);

namespace App\Domains\Hotels\Presentation\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Ramsey\Uuid\Uuid;

/**
 * SearchHotelsRequest — Запрос на поиск отелей (B2C).
 *
 * Валидирует параметры поиска: город, даты заезда/выезда,
 * количество гостей, диапазон цен, удобства.
 * Публичный эндпоинт — аутентификация не требуется.
 * Нормализует входные данные перед валидацией.
 *
 * @package App\Domains\Hotels\Presentation\Http\Requests
 */
final class SearchHotelsRequest extends FormRequest
{
    /**
     * Публичный эндпоинт поиска — авторизация не требуется.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Нормализуем входные данные перед валидацией.
     * Приводим city к нижнему регистру, обрезаем пробелы,
     * инициализируем capacity значением по умолчанию.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'city'     => mb_strtolower(trim((string) ($this->city ?? ''))),
            'capacity' => (int) ($this->capacity ?? 1),
            'per_page' => (int) ($this->per_page ?? 20),
        ]);
    }

    /**
     * Правила валидации поискового запроса.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'city'           => ['required', 'string', 'min:2', 'max:255'],
            'name'           => ['sometimes', 'nullable', 'string', 'max:255'],
            'rating'         => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:5'],
            'check_in_date'  => ['required', 'date', 'after_or_equal:today'],
            'check_out_date' => ['required', 'date', 'after:check_in_date'],
            'capacity'       => ['required', 'integer', 'min:1', 'max:50'],
            'amenities'      => ['sometimes', 'nullable', 'array'],
            'amenities.*'    => ['string', 'max:100'],
            'min_price'      => ['sometimes', 'nullable', 'integer', 'min:0'],
            'max_price'      => ['sometimes', 'nullable', 'integer', 'min:0', 'gt:min_price'],
            'per_page'       => ['sometimes', 'nullable', 'integer', 'min:1', 'max:100'],
            'sort_by'        => ['sometimes', 'nullable', 'string', 'in:price_asc,price_desc,rating_desc,rating_asc'],
        ];
    }

    /**
     * Дополнительная валидация после правил.
     * Проверяет, что период поиска не превышает 365 дней.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v): void {
            $checkIn  = $this->input('check_in_date');
            $checkOut = $this->input('check_out_date');

            if ($checkIn !== null && $checkOut !== null) {
                $nights = \Carbon\Carbon::parse($checkIn)->diffInDays(\Carbon\Carbon::parse($checkOut));

                if ($nights > 365) {
                    $v->errors()->add(
                        'check_out_date',
                        'Период поиска не может превышать 365 дней.'
                    );
                }
            }
        });
    }

    /**
     * Человекочитаемые сообщения об ошибках валидации.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'city.required'              => 'Укажите город для поиска отелей.',
            'city.min'                   => 'Название города должно содержать минимум 2 символа.',
            'city.max'                   => 'Название города не должно превышать 255 символов.',
            'check_in_date.required'     => 'Укажите дату заезда.',
            'check_in_date.after_or_equal' => 'Дата заезда не может быть в прошлом.',
            'check_out_date.required'    => 'Укажите дату выезда.',
            'check_out_date.after'       => 'Дата выезда должна быть позже даты заезда.',
            'capacity.required'          => 'Укажите количество гостей.',
            'capacity.min'               => 'Количество гостей должно быть не менее 1.',
            'capacity.max'               => 'Количество гостей не может превышать 50.',
            'rating.min'                 => 'Рейтинг должен быть от 0 до 5.',
            'rating.max'                 => 'Рейтинг должен быть от 0 до 5.',
            'max_price.gt'               => 'Максимальная цена должна быть больше минимальной.',
            'sort_by.in'                 => 'Неверный параметр сортировки.',
        ];
    }

    /**
     * Читаемые названия полей для вывода в сообщениях об ошибках.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'city'           => 'город',
            'check_in_date'  => 'дата заезда',
            'check_out_date' => 'дата выезда',
            'capacity'       => 'количество гостей',
            'rating'         => 'рейтинг',
            'min_price'      => 'минимальная цена',
            'max_price'      => 'максимальная цена',
            'sort_by'        => 'сортировка',
        ];
    }

    /**
     * Возвращает JSON-ответ с ошибками при провале валидации.
     * Включает correlation_id для трассировки.
     */
    protected function failedValidation(Validator $validator): never
    {
        $correlationId = $this->header('X-Correlation-ID') ?? Uuid::uuid4()->toString();

        throw new HttpResponseException(
            new \Illuminate\Http\JsonResponse([
                'success'        => false,
                'message'        => 'Ошибка валидации запроса на поиск отелей.',
                'errors'         => $validator->errors(),
                'correlation_id' => $correlationId,
            ], 422)
        );
    }
}

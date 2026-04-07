<?php

declare(strict_types=1);

namespace App\Domains\Art\Requests\B2B;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

/**
 * B2B Form Request: создание произведения искусства.
 *
 * CANON 2026 — Layer 7: Requests (B2B namespace).
 * Авторизация проверяет роли tenant_owner / b2b_manager.
 * Валидация включает B2B-специфичные поля: inn, business_card_id, MOQ.
 *
 * @package App\Domains\Art\Requests\B2B
 */
final class CreateArtworkRequest extends FormRequest
{
    /**
     * Авторизация: доступ только для владельцев бизнеса и B2B-менеджеров.
     */
    public function authorize(): bool
    {
        $user = $this->user();

        if ($user === null) {
            return false;
        }

        return $user->hasAnyRole(['tenant_owner', 'tenant_manager', 'b2b_manager']);
    }

    /**
     * Правила валидации для B2B создания произведения.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'title'             => ['required', 'string', 'min:2', 'max:255'],
            'artist_id'         => ['required', 'integer', 'min:1', 'exists:artists,id'],
            'project_id'        => ['nullable', 'integer', 'exists:projects,id'],
            'description'       => ['required', 'string', 'min:10', 'max:5000'],
            'price_cents'       => ['required', 'integer', 'min:100', 'max:100000000'],
            'technique'         => ['nullable', 'string', 'max:255'],
            'dimensions'        => ['nullable', 'array'],
            'dimensions.width'  => ['nullable', 'numeric', 'min:0.1'],
            'dimensions.height' => ['nullable', 'numeric', 'min:0.1'],
            'is_visible'        => ['sometimes', 'boolean'],
            'tags'              => ['nullable', 'array', 'max:20'],
            'tags.*'            => ['string', 'max:50'],
            'meta'              => ['nullable', 'array'],
            'inn'               => ['nullable', 'string', 'size:10'],
            'business_card_id'  => ['nullable', 'integer', 'exists:business_groups,id'],
        ];
    }

    /**
     * Кастомные сообщения об ошибках (русский язык).
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.required'       => 'Название произведения обязательно.',
            'title.max'            => 'Название не может быть длиннее 255 символов.',
            'artist_id.required'   => 'Укажите ID художника.',
            'artist_id.exists'     => 'Художник с таким ID не найден.',
            'description.required' => 'Описание произведения обязательно.',
            'description.min'      => 'Описание должно содержать не менее 10 символов.',
            'price_cents.required' => 'Цена обязательна.',
            'price_cents.min'      => 'Минимальная цена — 1 ₽ (100 копеек).',
            'tags.max'             => 'Максимум 20 тегов.',
        ];
    }

    /**
     * Названия полей для удобочитаемых ошибок.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'title'       => 'название',
            'artist_id'   => 'художник',
            'description' => 'описание',
            'price_cents' => 'цена (копейки)',
            'technique'   => 'техника',
            'tags'        => 'теги',
        ];
    }

    /**
     * Получить correlation_id из заголовка или сгенерировать новый.
     */
    public function correlationId(): string
    {
        return $this->header('X-Correlation-ID', (string) Str::uuid());
    }

    /**
     * Определить, является ли запрос B2B.
     */
    public function isB2B(): bool
    {
        return $this->has('inn') && $this->has('business_card_id');
    }

    /**
     * Подготовка данных перед валидацией.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'correlation_id' => $this->correlationId(),
        ]);
    }
}

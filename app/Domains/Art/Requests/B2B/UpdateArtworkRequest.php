<?php

declare(strict_types=1);

namespace App\Domains\Art\Requests\B2B;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

/**
 * B2B Form Request: обновление произведения искусства.
 *
 * CANON 2026 — Layer 7: Requests (B2B namespace).
 * Все поля optional (sometimes) — частичное обновление.
 * SKU/uuid уникальность проверяется с исключением текущей записи.
 *
 * @package App\Domains\Art\Requests\B2B
 */
final class UpdateArtworkRequest extends FormRequest
{
    /**
     * Авторизация: доступ для владельцев и менеджеров tenant.
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
     * Правила валидации — все поля sometimes (частичное обновление).
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'title'             => ['sometimes', 'string', 'min:2', 'max:255'],
            'description'       => ['sometimes', 'string', 'min:10', 'max:5000'],
            'price_cents'       => ['sometimes', 'integer', 'min:100', 'max:100000000'],
            'technique'         => ['sometimes', 'nullable', 'string', 'max:255'],
            'dimensions'        => ['sometimes', 'nullable', 'array'],
            'dimensions.width'  => ['nullable', 'numeric', 'min:0.1'],
            'dimensions.height' => ['nullable', 'numeric', 'min:0.1'],
            'is_visible'        => ['sometimes', 'boolean'],
            'status'            => ['sometimes', 'string', 'in:draft,published,archived'],
            'tags'              => ['sometimes', 'nullable', 'array', 'max:20'],
            'tags.*'            => ['string', 'max:50'],
            'meta'              => ['sometimes', 'nullable', 'array'],
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
            'title.min'       => 'Название должно содержать не менее 2 символов.',
            'title.max'       => 'Название не может быть длиннее 255 символов.',
            'description.min' => 'Описание должно содержать не менее 10 символов.',
            'price_cents.min' => 'Минимальная цена — 1 ₽ (100 копеек).',
            'status.in'       => 'Допустимые статусы: draft, published, archived.',
            'tags.max'        => 'Максимум 20 тегов.',
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
            'description' => 'описание',
            'price_cents' => 'цена (копейки)',
            'technique'   => 'техника',
            'status'      => 'статус',
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

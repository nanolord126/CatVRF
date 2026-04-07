<?php

declare(strict_types=1);

namespace App\Domains\VerticalName\Requests\B2B;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Request: создание VerticalItem через B2B Tenant Panel.
 *
 * CANON 2026 — Layer 7: Requests.
 * Валидация данных для B2B-операций в Tenant Panel.
 * Авторизация проверяет принадлежность к tenant.
 *
 * @package App\Domains\VerticalName\Requests\B2B
 */
final class CreateVerticalItemRequest extends FormRequest
{
    /**
     * Авторизация: только владельцы tenant или B2B-менеджеры.
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
     * Правила валидации.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'price_kopecks' => ['required', 'integer', 'min:100', 'max:100000000'],
            'sku' => ['nullable', 'string', 'max:100', 'unique:vertical_name_items,sku'],
            'category' => ['nullable', 'string', 'max:100'],
            'stock_quantity' => ['required', 'integer', 'min:0', 'max:1000000'],
            'is_active' => ['boolean'],
            'is_b2b_available' => ['boolean'],
            'image_url' => ['nullable', 'url', 'max:2048'],
            'tags' => ['nullable', 'array', 'max:20'],
            'tags.*' => ['string', 'max:50'],
            'metadata' => ['nullable', 'array'],
        ];
    }

    /**
     * Кастомные сообщения валидации.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Название товара обязательно.',
            'name.min' => 'Название товара должно содержать минимум 2 символа.',
            'name.max' => 'Название товара не должно превышать 255 символов.',
            'price_kopecks.required' => 'Цена обязательна.',
            'price_kopecks.min' => 'Минимальная цена — 1 рубль (100 копеек).',
            'price_kopecks.max' => 'Максимальная цена — 1 000 000 рублей.',
            'stock_quantity.required' => 'Количество на складе обязательно.',
            'stock_quantity.min' => 'Количество не может быть отрицательным.',
            'sku.unique' => 'Артикул (SKU) уже используется.',
            'tags.max' => 'Максимум 20 тегов.',
            'image_url.url' => 'URL изображения должен быть валидным.',
        ];
    }

    /**
     * Атрибуты для валидации.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'название',
            'description' => 'описание',
            'price_kopecks' => 'цена (копейки)',
            'sku' => 'артикул',
            'category' => 'категория',
            'stock_quantity' => 'остаток на складе',
            'is_active' => 'активен',
            'is_b2b_available' => 'доступен для B2B',
            'image_url' => 'URL изображения',
            'tags' => 'теги',
        ];
    }
}

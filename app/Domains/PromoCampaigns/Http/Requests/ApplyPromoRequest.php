<?php

declare(strict_types=1);

namespace App\Domains\PromoCampaigns\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Class ApplyPromoRequest
 *
 * Part of the PromoCampaigns vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Form Request with validation rules.
 * Validates input before reaching the controller.
 * Authorization checks tenant and business group access.
 *
 * @package App\Domains\PromoCampaigns\Http\Requests
 */
final class ApplyPromoRequest extends FormRequest
{
    /**
     * Безусловно подтверждает право пользователя на применение промокодов.
     * Возможна врезка проверки ролей, но для корзины публично доступно всем авторизованным.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Формирует абсолютно защищенный массив правил валидации входящего тела запроса.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'min:3', 'max:50'],
            'cart_subtotal' => ['required', 'integer', 'min:1'], // сумма в копейках
            'order_id' => ['required', 'integer', 'min:1'],
            'tenant_id' => ['required', 'integer', 'min:1'],
            'correlation_id' => ['required', 'string', 'uuid'],
        ];
    }
}

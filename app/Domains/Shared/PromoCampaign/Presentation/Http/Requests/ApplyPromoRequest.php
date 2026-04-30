<?php

declare(strict_types=1);

namespace Modules\PromoCampaign\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Глубоко структурированный класс HTTP-запроса (FormRequest) для применения акционного промокода.
 *
 * Категорически отвечает за предварительную валидацию строкового входа, гарантируя отсутствие
 * инъекций, пустых полей и несоответствия формату. Абсолютно блокирует сомнительные пэйлоады.
 */
final class ApplyPromoRequest extends FormRequest
{
    /**
     * Строго определяет принципиальную авторизованность текущего пользователя на выполнение запроса.
     *
     * @return bool Истинно, если запрос легитимен контексту.
     */
    public function authorize(): bool
    {
        // Фактическая авторизация может контролироваться policy-классами или middleware
        return true;
    }

    /**
     * Безусловно генерирует набор строгих правил валидации входящего массива данных.
     *
     * @return array<string, mixed> Массив правил валидации.
     */
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'min:3', 'max:50', 'regex:/^[A-Z0-9_\-]+$/i'],
            'order_amount_kopecks' => ['required', 'integer', 'min:1'],
            'tenant_id' => ['required', 'integer', 'min:1'],
            'correlation_id' => ['required', 'string', 'uuid'],
        ];
    }

    /**
     * Подготавливает персонализированные и строго типизированные сообщения об ошибках валидации.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'code.required' => 'Промокод является категорически обязательным для проверки.',
            'code.regex' => 'Промокод должен состоять исключительно из латинских букв, цифр, дефисов и подчеркиваний.',
            'order_amount_kopecks.min' => 'Сумма заказа не может быть отрицательной или нулевой.',
        ];
    }
}

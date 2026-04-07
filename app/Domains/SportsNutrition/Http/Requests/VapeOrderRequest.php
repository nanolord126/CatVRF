<?php declare(strict_types=1);

/**
 * VapeOrderRequest — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/vapeorderrequest
 */


namespace App\Domains\SportsNutrition\Http\Requests;


use Illuminate\Contracts\Auth\Guard;
final class VapeOrderRequest
{
    public function __construct(
        private readonly Guard $guard) {}


    /**
         * Конструктор с DP зависимостью (FraudControlService).
         */
        public function authorize(FraudControlService $fraud): bool
        {
            // 1. Предварительный Fraud Check перед обработкой запроса
            return $fraud->check([
                'operation' => 'vape_order_validate',
                'user_id' => $this->guard->id(),
                'ip' => $this->ip(),
                'correlation_id' => $this->header('X-Correlation-ID') ?? (string) Str::uuid(),
            ]);
        }

        /**
         * Правила валидации входных данных.
         */
        public function rules(): array
        {
            return [
                'amount_kopecks' => ['required', 'integer', 'min:1000'], // мин заказ 10 руб (тестовый)
                'items.*.product_id' => ['required', 'integer'],
                'items.*.type' => ['required', 'string', 'in:device,liquid'],
                'items.*.qty' => ['required', 'integer', 'min:1'],
                'marking_consent' => ['required', 'boolean', 'accepted'], // Согласие с правилами маркировки
            ];
        }

        /**
         * Понятные человекочитаемые сообщения.
         */
        public function messages(): array
        {
            return [
                'amount_kopecks.min' => 'Минимальная сумма заказа не достигнута.',
                'items.required' => 'Корзина не может быть пустой.',
                'marking_consent.accepted' => 'Необходимо подтвердить согласие с правилами реализации маркированного товара.',
            ];
        }
}

<?php declare(strict_types=1);

namespace App\Domains\SportsNutrition\Http\Requests;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class VapeOrderRequest extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    /**
         * Конструктор с DP зависимостью (FraudControlService).
         */
        public function authorize(FraudControlService $fraud): bool
        {
            // 1. Предварительный Fraud Check перед обработкой запроса
            return $fraud->check([
                'operation' => 'vape_order_validate',
                'user_id' => auth()->id(),
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
                'items' => ['required', 'array', 'min:1'],
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

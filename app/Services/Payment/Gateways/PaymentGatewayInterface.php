<?php declare(strict_types=1);

namespace App\Services\Payment\Gateways;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class PaymentGatewayInterface extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    /**
         * Инициирует платёж (с холдом или без).
         */
        /**
         * Выполнить операцию
         *
         * @return mixed
         * @throws \Exception
         */
        public function initPayment(array $data): array;

        /**
         * Возвращает статус платежа у провайдера.
         */
        /**
         * Выполнить операцию
         *
         * @return mixed
         * @throws \Exception
         */
        public function getStatus(string $providerPaymentId): array;

        /**
         * Подтверждает холд (Capture).
         */
        /**
         * Выполнить операцию
         *
         * @return mixed
         * @throws \Exception
         */
        public function capture(PaymentTransaction $transaction): bool;

        /**
         * Возврат средств.
         */
        /**
         * Выполнить операцию
         *
         * @return mixed
         * @throws \Exception
         */
        public function refund(PaymentTransaction $transaction, int $amount): bool;

        /**
         * Массовая выплата (payout). Для B2B-выплат бизнесу.
         */
        /**
         * Выполнить операцию
         *
         * @return mixed
         * @throws \Exception
         */
        public function createPayout(array $data): array;

        /**
         * Обработка webhook от провайдера. Возвращает распознанный статус.
         */
        /**
         * Выполнить операцию
         *
         * @return mixed
         * @throws \Exception
         */
        public function handleWebhook(array $payload): array;

        /**
         * ОФД-фискализация (54-ФЗ). Вызывается только после captured.
         */
        /**
         * Выполнить операцию
         *
         * @return mixed
         * @throws \Exception
         */
        public function fiscalize(PaymentTransaction $transaction): bool;
}

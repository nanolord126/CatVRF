<?php declare(strict_types=1);

namespace Modules\Payments\Gateways;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class PaymentGatewayInterface extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    /**
         * Выполнить операцию
         * 
         * @return mixed
         * @throws \Exception
         */
        public function createPayment(float $amount, string $orderId, array $data = []): array;
        /**
         * Выполнить операцию
         * 
         * @return mixed
         * @throws \Exception
         */
        public function checkStatus(string $paymentId): string;
        /**
         * Выполнить операцию
         * 
         * @return mixed
         * @throws \Exception
         */
        public function refund(string $paymentId, float $amount): bool;
}

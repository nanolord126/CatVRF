<?php declare(strict_types=1);

namespace App\Domains\Beauty\Services;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class B2BService extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function __construct(
            private readonly FraudControlService $fraudControl,
            private readonly WalletService $walletService
        ) {}

        public function createB2BOrder(array $data, string $correlationId): B2BBeautyOrder
        {
            return DB::transaction(function () use ($data, $correlationId) {
                $this->fraudControl->check($data, 'b2b_beauty_order_create');

                $order = B2BBeautyOrder::create(array_merge($data, [
                    'correlation_id' => $correlationId,
                ]));

                Log::channel('audit')->info('B2B Beauty order created', [
                    'order_id' => $order->id,
                    'correlation_id' => $correlationId,
                ]);

                return $order;
            });
        }
}

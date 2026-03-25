declare(strict_types=1);

<?php declare(strict_types=1);

namespace App\Domains\Beauty\Services;

use App\Domains\Beauty\Models\B2BBeautyOrder;
use App\Services\FraudControlService;
use App\Services\WalletService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final /**
 * B2BService
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class B2BService
{
    public function __construct(
        private readonly FraudControlService $fraudControl,
        private readonly WalletService $walletService
    ) {
    /**
     * Инициализировать класс
     */
    public function __construct()
    {
        // TODO: инициализация
    }
}

    public function createB2BOrder(array $data, string $correlationId): B2BBeautyOrder
    {
        return $this->db->transaction(function () use ($data, $correlationId) {
            $this->fraudControl->check($data, 'b2b_beauty_order_create');

            $order = B2BBeautyOrder::create(array_merge($data, [
                'correlation_id' => $correlationId,
            ]));

            $this->log->channel('audit')->info('B2B Beauty order created', [
                'order_id' => $order->id,
                'correlation_id' => $correlationId,
            ]);

            return $order;
        });
    }
}

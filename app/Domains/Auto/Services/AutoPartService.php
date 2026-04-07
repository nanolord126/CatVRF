<?php declare(strict_types=1);

namespace App\Domains\Auto\Services;




use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
use Illuminate\Http\Request;
final readonly class AutoPartService
{

    public function __construct(private FraudControlService $fraud,
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly Request $request, private readonly LoggerInterface $logger, private readonly Guard $guard) {}

        /**
         * Поиск совместимых запчастей по полному VIN или маске модели.
         *
         * @param string $vin 17-значный идентификатор
         * @param string|null $correlationId
         */
        public function findPartsByVin(string $vin, ?string $correlationId = null): Collection
        {
            $this->logger->info('VIN Search initiated', [
                'vin' => $vin,
                'correlation_id' => $correlationId,
            ]);

            // 1. Предварительная валидация VIN (проверка структуры через модель)
            if (!AutoVehicle::isValidVin($vin)) {
                $this->logger->warning('Invalid VIN search attempt', ['vin' => $vin]);
                return collect();
            }

            // 2. Поиск в БД по JSON полю compatibility_vin
            // В реальном проекте здесь может быть интеграция с внешним API (TecDoc, Laximo)
            return AutoPart::whereJsonContains('compatibility_vin', $vin)
                ->where('stock_quantity', '>', 0)
                ->get();
        }

        /**
         * Создание новой запчасти в каталоге с проверкой фрода.
         */
        public function createPart(array $data, string $correlationId): AutoPart
        {
            return $this->db->transaction(function () use ($data, $correlationId) {
                // 1. Fraud Check перед мутацией
                $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'auto_part_creation', amount: 0, correlationId: $correlationId ?? '');

                // 2. Создание записи
                $part = AutoPart::create(array_merge($data, [
                    'correlation_id' => $correlationId,
                ]));

                $this->logger->info('Auto Part created', [
                    'uuid' => $part->uuid,
                    'sku' => $part->sku,
                    'correlation_id' => $correlationId,
                ]);

                return $part;
            });
        }

        /**
         * Резервирование запчасти для заказ-наряда (Inventory Integration).
         */
        public function reserveForOrder(AutoPart $part, int $quantity, string $orderUuid): bool
        {
            return $this->db->transaction(function () use ($part, $quantity, $orderUuid) {
                if ($part->stock_quantity < $quantity) {
                    throw new \RuntimeException("Insufficient stock for part: {$part->sku}");
                }

                $part->decrement('stock_quantity', $quantity);

                $this->logger->info('Part reserved for order', [
                    'part_uuid' => $part->uuid,
                    'quantity' => $quantity,
                    'order_uuid' => $orderUuid,
                ]);

                return true;
            });
        }
}

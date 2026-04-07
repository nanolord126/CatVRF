<?php declare(strict_types=1);

namespace App\Domains\Sports\SportingGoods\Services;

use App\Domains\Sports\SportingGoods\Models\SportProduct;
use App\Services\FraudControlService;
use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;

/**
 * Сервис управления спортивными товарами.
 *
 * Отвечает за создание, обновление и поиск спорттоваров.
 * Все мутации проходят через FraudControlService и оборачиваются в транзакцию.
 *
 * @package App\Domains\Sports\SportingGoods\Services
 */
final readonly class SportingGoodsService
{
    public function __construct(
        private FraudControlService $fraud,
        private readonly \Illuminate\Database\DatabaseManager $db,
        private readonly LoggerInterface $logger,
        private readonly Guard $guard,
    ) {}

    /**
     * Создать новый спорттовар в каталоге.
     *
     * @param array<string, mixed> $data Данные товара (name, price, tags)
     * @param string $correlationId Идентификатор корреляции для трейсинга
     * @return SportProduct Созданный товар
     *
     * @throws \DomainException При ошибке fraud-проверки
     */
        public function createProduct(array $data, string $correlationId): SportProduct
        {
            $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'mutation', amount: 0, correlationId: $correlationId ?? '');
    return $this->db->transaction(function () use ($data, $correlationId) {
                $this->logger->info("СОЗДАНИЕ СПОРТТОВАРА", ["correlation_id" => $correlationId]);

                $product = SportProduct::create([
                    "tenant_id" => tenant("id") ?? 1,
                    "correlation_id" => $correlationId,
                    "name" => $data["name"] ?? "Спорттовар",
                    "price" => $data["price"] ?? 0,
                    "tags" => []
                ]);

                $this->logger->info("СПОРТТОВАР СОЗДАН", ["correlation_id" => $correlationId, "id" => $product->id]);

                return $product;
            });
        }

    /**
     * Get the string representation of this instance.
     *
     * @return string The string representation
     */
    public function __toString(): string
    {
        return static::class;
    }

    /**
     * Get debug information for this instance.
     *
     * @return array<string, mixed> Debug data including class name and state
     */
    public function toDebugArray(): array
    {
        return [
            'class' => static::class,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}

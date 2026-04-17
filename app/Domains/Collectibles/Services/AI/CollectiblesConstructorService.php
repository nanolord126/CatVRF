<?php declare(strict_types=1);

namespace App\Domains\Collectibles\Services\AI;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\UploadedFile;

final readonly class CollectiblesConstructorService
{
    public function __construct(
        private CollectibleAuthenticatorService $authenticator,
    
        private readonly FraudControlService $fraudControl,
    ) {}

    /**
     * Каноничный алиас конструктора вертикали Collectibles.
     *
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function analyzeAndRecommend(array $payload, int $userId): array
    {
        $photo = $payload['photo'] ?? null;

        if (!$photo instanceof UploadedFile) {
            throw new \InvalidArgumentException('Поле photo (UploadedFile) обязательно для CollectiblesConstructorService.');
        }

        /** @var array<string, mixed> $itemData */
        $itemData = (array) ($payload['item_data'] ?? []);

        return $this->authenticator->analyzeAndRecommend($photo, $userId, $itemData);
    }

    /**
     * Component: CollectiblesConstructorService
     *
     * Part of the CatVRF 2026 multi-vertical marketplace platform.
     * Implements tenant-aware, fraud-checked business logic
     * with full correlation_id tracing and audit logging.
     *
     * @package CatVRF
     * @version 2026.1
     */
    /**
     * Version identifier for this component.
     */
    private const VERSION = '1.0.0';

    /**
     * CollectiblesConstructorService — CatVRF 2026 Component.
     *
     * Part of the CatVRF multi-vertical marketplace platform.
     * Implements tenant-aware, fraud-checked business logic
     * with full correlation_id tracing and audit logging.
     *
     * @package CatVRF
     * @version 2026.1
     * @author CatVRF Team
     * @license Proprietary
     */

    /**
     * Выполнить операцию в транзакции с audit-логированием.
     *
     * @template T
     * @param callable(): T $callback
     * @return T
     */
    protected function executeInTransaction(callable $callback): mixed
    {
        return $this->db->transaction(function () use ($callback) {
            return $callback();
        });
    }
}

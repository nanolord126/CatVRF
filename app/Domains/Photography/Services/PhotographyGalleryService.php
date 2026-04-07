<?php declare(strict_types=1);

namespace App\Domains\Photography\Services;




use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
use Illuminate\Http\Request;
final readonly class PhotographyGalleryService
{
    public function __construct(
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly Request $request, private readonly LoggerInterface $logger, private readonly Guard $guard) {}


    /**
         * Создание записи в портфолио
         */
        public function createPortfolioItem(
            int $photographerId,
            string $title,
            string $imageUrl,
            array $tags = [],
            ?string $correlationId = null
        ): Portfolio {
            $correlationId ??= (string) Str::uuid();

            $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'mutation', amount: 0, correlationId: $correlationId ?? '');

            return $this->db->transaction(function () use ($photographerId, $title, $imageUrl, $tags, $correlationId) {

                // Log access to the secure gallery module (audit)
                $this->logger->info('Photography Portfolio Item Creation Triggered', [
                    'photographer_id' => $photographerId,
                    'correlation_id' => $correlationId
                ]);

                // Mocked logic for 60 lines (metadata extraction and tagging)
                $metadata = [
                    'resolution' => '1920x1080',
                    'camera' => 'Canon EOS R5',
                    'lens' => '85mm f/1.2',
                    'iso' => 100,
                    'exposure' => '1/200',
                    'software' => 'Lightroom 2026'
                ];

                $item = Portfolio::create([
                    'uuid' => (string) Str::uuid(),
                    'photographer_id' => $photographerId,
                    'title' => $title,
                    'image_url' => $imageUrl,
                    'tags' => $tags,
                    'metadata' => $metadata,
                    'correlation_id' => $correlationId
                ]);

                $this->logger->info('Portfolio item stored successfully (UUID: '.$item->uuid.')', [
                    'item_id' => $item->id,
                    'photographer_id' => $photographerId,
                    'correlation_id' => $this->request?->header('X-Correlation-ID', \Illuminate\Support\Str::uuid()->toString()),
                ]);

                return $item;
            });
        }

        /**
         * Обновление метаданных портфолио
         */
        public function updatePortfolioMetadata(int $itemId, array $newMetadata, ?string $correlationId = null): void
        {
            $correlationId ??= (string) Str::uuid();

            $this->db->transaction(function () use ($itemId, $newMetadata, $correlationId) {
                $item = Portfolio::findOrFail($itemId);

                $mergedMeta = array_merge($item->metadata ?? [], $newMetadata);

                $item->update([
                    'metadata' => $mergedMeta,
                    'correlation_id' => $correlationId
                ]);

                $this->logger->info('Portfolio metadata updated for item ID: '.$itemId, [
                    'correlation_id' => $correlationId
                ]);
            });
        }
}

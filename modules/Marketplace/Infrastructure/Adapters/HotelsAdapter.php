<?php

declare(strict_types=1);

namespace Modules\Marketplace\Infrastructure\Adapters;

use Modules\Hotels\Domain\Entities\Room;
use Modules\Hotels\Domain\Repositories\RoomRepositoryInterface;
use Modules\Marketplace\Domain\ValueObjects\VerticalSource;
use Psr\Log\LoggerInterface;

final class HotelsAdapter implements VerticalAdapterInterface
{
    public function __construct(
        private readonly RoomRepositoryInterface $roomRepository,
        private readonly LoggerInterface $logger,
    ) {}

    public function fetchSources(array $filters = [], int $limit = 100): array
    {
        $this->logger->info('Fetching Hotels rooms', ['filters' => $filters, 'limit' => $limit]);

        $rooms = $this->roomRepository->findActive($limit);

        $sources = [];
        foreach ($rooms as $room) {
            try {
                $sources[] = $this->convertRoomToArray($room);
            } catch (\Throwable $e) {
                $this->logger->error('Failed to convert room', [
                    'room_id' => $room->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $sources;
    }

    public function fetchSingle(int $sourceId, array $filters = []): ?array
    {
        $room = $this->roomRepository->findById($sourceId);
        if ($room === null) {
            return null;
        }

        return $this->convertRoomToArray($room);
    }

    public function countSources(array $filters = []): int
    {
        return $this->roomRepository->countActive();
    }

    public function validateSource(array $source): bool
    {
        $required = ['id', 'title', 'price', 'category'];
        foreach ($required as $field) {
            if (!isset($source[$field])) {
                return false;
            }
        }

        return !empty($source['title']) && $source['price'] > 0;
    }

    public function getSupportedEntityTypes(): array
    {
        return ['booking', 'room'];
    }

    public function getDefaultFilters(): array
    {
        return [
            'status' => 'active',
            'is_available' => true,
            'auto_publish' => true,
        ];
    }

    private function convertRoomToArray(Room $room): array
    {
        return [
            'id' => $room->id,
            'title' => $room->name,
            'description' => $room->description ?? '',
            'price' => $room->pricePerNight,
            'currency' => 'RUB',
            'category' => $this->mapCategory($room->roomType),
            'type' => 'booking',
            'tags' => array_merge(
                $room->amenities ?? [],
                $room->hasBreakfast ? ['breakfast'] : [],
                $room->hasWiFi ? ['wifi'] : [],
                $room->hasParking ? ['parking'] : [],
            ),
            'images' => $room->images ?? [],
            'attributes' => [
                'hotel_id' => $room->hotelId,
                'capacity' => $room->capacity,
                'bed_count' => $room->bedCount,
                'room_size' => $room->sizeSqM,
                'floor' => $room->floor,
            ],
            'metadata' => [
                'vertical' => VerticalSource::HOTELS->value,
                'source_type' => 'room',
                'supports_booking' => true,
            ],
            'tenant_id' => $room->tenantId,
            'business_group_id' => $room->businessGroupId,
        ];
    }

    private function mapCategory(?string $category): string
    {
        $mapping = [
            'standard' => 'standard',
            'deluxe' => 'deluxe',
            'suite' => 'suite',
            'family' => 'family',
            'penthouse' => 'luxury',
        ];

        return $mapping[$category ?? ''] ?? 'hotels';
    }
}

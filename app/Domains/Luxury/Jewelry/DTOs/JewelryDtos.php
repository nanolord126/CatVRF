<?php declare(strict_types=1);

namespace App\Domains\Luxury\Jewelry\DTOs;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class JewelryProductDto extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function __construct(
            public string $name,
            public string $sku,
            public int $storeId,
            public int $categoryId,
            public int $priceB2c,
            public int $priceB2b,
            public int $stockQuantity,
            public string $metalType,
            public string $metalFineness,
            public ?float $weightGrams = null,
            public ?array $gemstones = null,
            public ?int $collectionId = null,
            public bool $hasCertification = false,
            public ?string $certificateNumber = null,
            public bool $isCustomizable = false,
            public bool $isGiftWrapped = false,
            public bool $isPublished = false,
            public ?array $tags = null,
            public ?string $correlationId = null
        ) {}

        public static function fromRequest(array $data, ?string $correlationId = null): self
        {
            return new self(
                name: $data['name'],
                sku: $data['sku'],
                storeId: (int) $data['store_id'],
                categoryId: (int) $data['category_id'],
                priceB2c: (int) $data['price_b2c'],
                priceB2b: (int) $data['price_b2b'],
                stockQuantity: (int) $data['stock_quantity'],
                metalType: $data['metal_type'],
                metalFineness: $data['metal_fineness'],
                weightGrams: isset($data['weight_grams']) ? (float) $data['weight_grams'] : null,
                gemstones: $data['gemstones'] ?? null,
                collectionId: isset($data['collection_id']) ? (int) $data['collection_id'] : null,
                hasCertification: (bool) ($data['has_certification'] ?? false),
                certificateNumber: $data['certificate_number'] ?? null,
                isCustomizable: (bool) ($data['is_customizable'] ?? false),
                isGiftWrapped: (bool) ($data['is_gift_wrapped'] ?? false),
                isPublished: (bool) ($data['is_published'] ?? false),
                tags: $data['tags'] ?? null,
                correlationId: $correlationId
            );
        }
    }

    /**
     * JewelryCustomOrderDto (Layer 2/9)
     */
    final readonly class JewelryCustomOrderDto
    {
        public function __construct(
            public int $storeId,
            public int $userId,
            public string $customerName,
            public string $customerPhone,
            public int $estimatedPrice,
            public array $aiSpecification,
            public ?string $userNotes = null,
            public ?string $referencePhotoPath = null,
            public ?string $correlationId = null
        ) {}
    }

    /**
     * AIJewelryConstructorRequestDto (Layer 2/9)
     */
    final readonly class AIJewelryConstructorRequestDto
    {
        public function __construct(
            public string $stylePreference, // minimalist, vintage, luxury, art-deco
            public string $colorType, // warm-spring, cool-summer, warm-autumn, cool-winter
            public string $occasion, // wedding, party, everyday, corporate
            public int $budgetLimit, // Max Price in Kopecks
            public ?string $photoPath = null,
            public ?string $correlationId = null
        ) {}
    }

    /**
     * AIJewelryResultDto (Layer 2/9)
     */
    final readonly class AIJewelryResultDto
    {
        public function __construct(
            public array $recommendedProductIds,
            public array $suggestedMetals,
            public array $suggestedStones,
            public string $aiAdviceBrief,
            public ?string $correlationId = null
        ) {}
}
